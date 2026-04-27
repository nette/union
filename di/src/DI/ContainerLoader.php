<?php declare(strict_types=1);

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

namespace Nette\DI;

use Nette;
use function bin2hex, class_exists, file_get_contents, file_put_contents, flock, fopen, function_exists, hash, is_file, preg_quote, preg_replace, random_bytes, rename, serialize, sprintf, strlen, substr, unlink, unserialize, usleep;


/**
 * DI container loader.
 */
class ContainerLoader
{
	public function __construct(
		private readonly string $tempDirectory,
		private readonly bool $autoRebuild = false,
	) {
	}


	/**
	 * Loads the container class, generating it if not already cached. Returns the class name.
	 * @param  callable(Compiler): ?string  $generator
	 * @return class-string<Container>
	 */
	public function load(callable $generator, mixed $key = null): string
	{
		$class = $this->getClassName($key);
		return $this->loadFile($class, \Closure::fromCallable($generator));
	}


	/**
	 * Returns the container class name derived from the given key.
	 */
	public function getClassName(mixed $key): string
	{
		return 'Container_' . substr(hash('xxh128', serialize($key)), 0, 10);
	}


	/**
	 * @param  (\Closure(Compiler): ?string)  $generator
	 * @return class-string<Container>
	 */
	private function loadFile(string $class, \Closure $generator): string
	{
		$file = "$this->tempDirectory/$class.php";
		$alreadyLoaded = class_exists($class, autoload: false);

		if (!$this->isExpired($file)) {
			if ($alreadyLoaded) {
				return $class;
			} elseif ((@include $file) !== false) { // @ file may not exist
				return $class;
			}
		}

		Nette\Utils\FileSystem::createDir($this->tempDirectory);

		$handle = @fopen("$file.lock", 'c+'); // @ is escalated to exception
		if (!$handle) {
			throw new Nette\IOException(sprintf("Unable to create file '%s.lock'. %s", $file, Nette\Utils\Helpers::getLastError()));
		} elseif (!@flock($handle, LOCK_EX)) { // @ is escalated to exception
			throw new Nette\IOException(sprintf("Unable to acquire exclusive lock on '%s.lock'. %s", $file, Nette\Utils\Helpers::getLastError()));
		}

		$codeRegenerated = false;
		if (!is_file($file) || $this->isExpired($file, $updatedMeta)) {
			if (isset($updatedMeta)) {
				$toWrite["$file.meta"] = $updatedMeta;
			} else {
				[$toWrite[$file], $toWrite["$file.meta"]] = $this->generate($class, $generator);
				$codeRegenerated = true;
			}

			foreach ($toWrite as $name => $content) {
				$this->atomicWrite($name, $content);
			}
		}

		flock($handle, LOCK_UN);

		if (!$alreadyLoaded) {
			if ((@include $file) === false) { // @ - error escalated to exception
				throw new Nette\IOException(sprintf("Unable to include '%s'.", $file));
			}
			return $class;
		}

		// PHP cannot redeclare the loaded class, so reload the regenerated code under a new name
		if ($this->autoRebuild && $codeRegenerated) {
			return $this->reloadAsUnique($class, $file);
		}

		return $class;
	}


	/**
	 * Atomically writes $content to $file through a temporary file and rename().
	 *
	 * On Windows the rename intermittently fails with "Access is denied" when the target is
	 * momentarily locked (antivirus or a memory-mapped opcache handle); unlike POSIX the replace
	 * is not atomic against open handles. So the opcache handle is dropped first and the rename
	 * retried briefly. Elsewhere a single failure throws at once.
	 */
	private function atomicWrite(string $file, string $content): void
	{
		$tmp = "$file.tmp";
		if (file_put_contents($tmp, $content) !== strlen($content)) {
			@unlink($tmp); // @ - file may not exist
			throw new Nette\IOException(sprintf("Unable to create file '%s'. %s", $file, Nette\Utils\Helpers::getLastError()));
		}

		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, force: true); // @ can be restricted; frees a possible handle on the old target
		}

		for ($attempt = 1; !@rename($tmp, $file); $attempt++) { // @ is escalated to exception below
			if ($attempt >= 3 || !Nette\Utils\Helpers::IsWindows) {
				@unlink($tmp); // @ - file may not exist
				throw new Nette\IOException(sprintf("Unable to create file '%s'. %s", $file, Nette\Utils\Helpers::getLastError()));
			}
			usleep(100_000);
		}

		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, force: true); // @ can be restricted; refresh with the new content
		}
	}


	/**
	 * Loads a regenerated container file under a fresh, unique class name via eval().
	 * @return class-string<Container>
	 */
	private function reloadAsUnique(string $class, string $file): string
	{
		$unique = $class . '_R' . substr(bin2hex(random_bytes(4)), 0, 8);
		$code = @file_get_contents($file); // @ - file may not exist
		if ($code === false) {
			throw new Nette\IOException(sprintf("Unable to read '%s' for live reload.", $file));
		}

		$count = 0;
		$code = preg_replace(
			'~\bclass\s+' . preg_quote($class, '~') . '\b~',
			"class $unique",
			$code,
			limit: 1,
			count: $count,
		);
		if ($code === null || $count !== 1) {
			throw new Nette\InvalidStateException(sprintf("Unable to rename class '%s' for live reload (expected 1 replacement, got %d).", $class, $count));
		}

		$code = preg_replace('~^\s*<\?php\s*~', '', $code, limit: 1); // eval() must not receive <?php
		eval($code);

		if (!class_exists($unique, autoload: false)) {
			throw new Nette\InvalidStateException(sprintf("Live reload eval failed: class '%s' was not defined.", $unique));
		}

		return $unique;
	}


	private function isExpired(string $file, ?string &$updatedMeta = null): bool
	{
		if ($this->autoRebuild) {
			$meta = @unserialize((string) file_get_contents("$file.meta")); // @ - file may not exist
			$orig = $meta[2] ?? null;
			return empty($meta[0])
				|| DependencyChecker::isExpired(...$meta)
				|| ($orig !== $meta[2] && $updatedMeta = serialize($meta));
		}

		return false;
	}


	/**
	 * @param  callable(Compiler): ?string  $generator
	 * @return array{string, string} code, file
	 */
	protected function generate(string $class, callable $generator): array
	{
		$compiler = new Compiler;
		$compiler->setClassName($class);
		$code = $generator(...[&$compiler]) ?? $compiler->compile();
		return [
			"<?php\n$code",
			serialize($compiler->exportDependencies()),
		];
	}
}

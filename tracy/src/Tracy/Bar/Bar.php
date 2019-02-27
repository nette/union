<?php

/**
 * This file is part of the Tracy (https://tracy.nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Tracy;


/**
 * Debug Bar.
 */
class Bar
{
	/** @var IBarPanel[] */
	private $panels = [];

	/** @var bool  initialized by dispatchAssets() */
	private $useSession = false;

	/** @var string|NULL  generated by renderLoader() */
	private $contentId;


	/**
	 * Add custom panel.
	 * @return static
	 */
	public function addPanel(IBarPanel $panel, string $id = null): self
	{
		if ($id === null) {
			$c = 0;
			do {
				$id = get_class($panel) . ($c++ ? "-$c" : '');
			} while (isset($this->panels[$id]));
		}
		$this->panels[$id] = $panel;
		return $this;
	}


	/**
	 * Returns panel with given id
	 */
	public function getPanel(string $id): ?IBarPanel
	{
		return $this->panels[$id] ?? null;
	}


	/**
	 * Renders loading <script>
	 */
	public function renderLoader(): void
	{
		if (!$this->useSession) {
			throw new \LogicException('Start session before Tracy is enabled.');
		}
		$contentId = $this->contentId = $this->contentId ?: substr(md5(uniqid('', true)), 0, 10);
		$nonce = Helpers::getNonce();
		$async = true;
		require __DIR__ . '/assets/loader.phtml';
	}


	/**
	 * Renders debug bar.
	 */
	public function render(): void
	{
		$useSession = $this->useSession && session_status() === PHP_SESSION_ACTIVE;
		$redirectQueue = &$_SESSION['_tracy']['redirect'];

		foreach (['bar', 'redirect', 'bluescreen'] as $key) {
			$queue = &$_SESSION['_tracy'][$key];
			$queue = array_slice((array) $queue, -10, null, true);
			$queue = array_filter($queue, function ($item) {
				return isset($item['time']) && $item['time'] > time() - 60;
			});
		}

		$rows = [];

		if (Helpers::isAjax()) {
			if ($useSession) {
				$contentId = $_SERVER['HTTP_X_TRACY_AJAX'];
				$row = (object) ['type' => 'ajax', 'panels' => $this->renderPanels('-ajax:' . $contentId)];
				$_SESSION['_tracy']['bar'][$contentId] = ['content' => self::renderHtmlRows([$row]), 'time' => time()];
			}

		} elseif (preg_match('#^Location:#im', implode("\n", headers_list()))) { // redirect
			if ($useSession) {
				$redirectQueue[] = [
					'panels' => $this->renderPanels('-r' . count($redirectQueue)),
					'time' => time(),
				];
			}

		} elseif (Helpers::isHtmlMode()) {
			$rows[] = (object) ['type' => 'main', 'panels' => $this->renderPanels()];
			foreach (array_reverse((array) $redirectQueue) as $info) {
				$rows[] = (object) ['type' => 'redirect', 'panels' => $info['panels']];
			}
			$redirectQueue = null;
			$content = self::renderHtmlRows($rows);

			if ($this->contentId) {
				$_SESSION['_tracy']['bar'][$this->contentId] = ['content' => $content, 'time' => time()];
			} else {
				$contentId = substr(md5(uniqid('', true)), 0, 10);
				$nonce = Helpers::getNonce();
				$async = false;
				require __DIR__ . '/assets/loader.phtml';
			}
		}
	}


	private static function renderHtmlRows(array $rows): string
	{
		ob_start(function () {});
		require __DIR__ . '/assets/panels.phtml';
		require __DIR__ . '/assets/bar.phtml';
		return Helpers::fixEncoding(ob_get_clean());
	}


	private function renderPanels(string $suffix = ''): array
	{
		set_error_handler(function (int $severity, string $message, string $file, int $line) {
			if (error_reporting() & $severity) {
				throw new \ErrorException($message, 0, $severity, $file, $line);
			}
		});

		$obLevel = ob_get_level();
		$panels = [];

		foreach ($this->panels as $id => $panel) {
			$idHtml = preg_replace('#[^a-z0-9]+#i', '-', $id) . $suffix;
			try {
				$tab = $panel->getTab();
				$panelHtml = $tab ? $panel->getPanel() : null;

			} catch (\Throwable $e) {
				while (ob_get_level() > $obLevel) { // restore ob-level if broken
					ob_end_clean();
				}
				$idHtml = "error-$idHtml";
				$tab = "Error in $id";
				$panelHtml = "<h1>Error: $id</h1><div class='tracy-inner'>" . nl2br(Helpers::escapeHtml($e)) . '</div>';
				unset($e);
			}
			$panels[] = (object) ['id' => $idHtml, 'tab' => $tab, 'panel' => $panelHtml];
		}

		restore_error_handler();
		return $panels;
	}


	/**
	 * Renders debug bar assets.
	 */
	public function dispatchAssets(): bool
	{
		$asset = $_GET['_tracy_bar'] ?? null;
		if ($asset === 'js') {
			header('Content-Type: application/javascript');
			header('Cache-Control: max-age=864000');
			header_remove('Pragma');
			header_remove('Set-Cookie');
			$this->renderAssets();
			return true;
		}

		$this->useSession = session_status() === PHP_SESSION_ACTIVE;

		if ($this->useSession && Helpers::isAjax()) {
			header('X-Tracy-Ajax: 1'); // session must be already locked
		}

		if ($this->useSession && $asset && preg_match('#^content(-ajax)?\.(\w+)$#', $asset, $m)) {
			$session = &$_SESSION['_tracy']['bar'][$m[2]];
			header('Content-Type: application/javascript');
			header('Cache-Control: max-age=60');
			header_remove('Set-Cookie');
			if (!$m[1]) {
				$this->renderAssets();
			}
			if ($session) {
				$method = $m[1] ? 'loadAjax' : 'init';
				echo "Tracy.Debug.$method(", json_encode($session['content'], JSON_UNESCAPED_SLASHES), ');';
				$session = null;
			}
			$session = &$_SESSION['_tracy']['bluescreen'][$m[2]];
			if ($session) {
				echo 'Tracy.BlueScreen.loadAjax(', json_encode($session['content'], JSON_UNESCAPED_SLASHES), ');';
				$session = null;
			}
			return true;
		}

		return false;
	}


	private function renderAssets(): void
	{
		$css = array_map('file_get_contents', array_merge([
			__DIR__ . '/assets/bar.css',
			__DIR__ . '/../Toggle/toggle.css',
			__DIR__ . '/../Dumper/assets/dumper.css',
			__DIR__ . '/../BlueScreen/assets/bluescreen.css',
		], Debugger::$customCssFiles));

		echo
"'use strict';
(function(){
	var el = document.createElement('style');
	el.setAttribute('nonce', document.currentScript.getAttribute('nonce') || document.currentScript.nonce);
	el.className='tracy-debug';
	el.textContent=" . json_encode(preg_replace('#\s+#u', ' ', implode($css))) . ";
	document.head.appendChild(el);})
();\n";

		array_map('readfile', array_merge([
			__DIR__ . '/assets/bar.js',
			__DIR__ . '/../Toggle/toggle.js',
			__DIR__ . '/../TableSort/table-sort.js',
			__DIR__ . '/../Dumper/assets/dumper.js',
			__DIR__ . '/../BlueScreen/assets/bluescreen.js',
		], Debugger::$customJsFiles));
	}
}

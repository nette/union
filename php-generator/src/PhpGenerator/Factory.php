<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */

declare(strict_types=1);

namespace Nette\PhpGenerator;

use Nette;


/**
 * Creates a representation based on reflection.
 */
final class Factory
{
	use Nette\SmartObject;

	public function fromClassReflection(\ReflectionClass $from, bool $withBodies = false): ClassType
	{
		if ($withBodies && $from->isAnonymous()) {
			throw new Nette\NotSupportedException('The $withBodies parameter cannot be used for anonymous functions.');
		}

		$class = $from->isAnonymous()
			? new ClassType
			: new ClassType($from->getShortName(), new PhpNamespace($from->getNamespaceName()));

		if (PHP_VERSION_ID >= 80100 && $from->isEnum()) {
			$class->setType($class::TYPE_ENUM);
			$from = new \ReflectionEnum($from->getName());
			$enumIface = $from->isBacked() ? \BackedEnum::class : \UnitEnum::class;
		} else {
			$class->setType($from->isInterface() ? $class::TYPE_INTERFACE : ($from->isTrait() ? $class::TYPE_TRAIT : $class::TYPE_CLASS));
			$class->setFinal($from->isFinal() && $class->isClass());
			$class->setAbstract($from->isAbstract() && $class->isClass());
			$enumIface = null;
		}

		$ifaces = $from->getInterfaceNames();
		foreach ($ifaces as $iface) {
			$ifaces = array_filter($ifaces, function (string $item) use ($iface): bool {
				return !is_subclass_of($iface, $item);
			});
		}
		if ($from->isInterface()) {
			$class->setExtends($ifaces);
		} else {
			$ifaces = array_diff($ifaces, [$enumIface]);
			$class->setImplements($ifaces);
		}

		$class->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		$class->setAttributes(self::getAttributes($from));
		if ($from->getParentClass()) {
			$class->setExtends($from->getParentClass()->name);
			$class->setImplements(array_diff($class->getImplements(), $from->getParentClass()->getInterfaceNames()));
		}

		$props = [];
		foreach ($from->getProperties() as $prop) {
			if ($prop->isDefault()
				&& $prop->getDeclaringClass()->name === $from->name
				&& (PHP_VERSION_ID < 80000 || !$prop->isPromoted())
				&& !$class->isEnum()
			) {
				$props[] = $this->fromPropertyReflection($prop);
			}
		}
		$class->setProperties($props);

		$methods = $bodies = [];
		foreach ($from->getMethods() as $method) {
			if (
				$method->getDeclaringClass()->name === $from->name
				&& (!$enumIface || !method_exists($enumIface, $method->name))
			) {
				$methods[] = $m = $this->fromMethodReflection($method);
				if ($withBodies) {
					$srcMethod = Nette\Utils\Reflection::getMethodDeclaringMethod($method);
					$srcClass = $srcMethod->getDeclaringClass();
					$b = $bodies[$srcClass->name] = $bodies[$srcClass->name] ?? $this->getExtractor($srcClass)->extractMethodBodies($srcClass->name);
					if (isset($b[$srcMethod->name])) {
						$m->setBody($b[$srcMethod->name]);
					}
				}
			}
		}
		$class->setMethods($methods);

		$consts = $cases = [];
		foreach ($from->getReflectionConstants() as $const) {
			if ($class->isEnum() && $from->hasCase($const->name)) {
				$cases[] = $this->fromCaseReflection($const);
			} elseif ($const->getDeclaringClass()->name === $from->name) {
				$consts[] = $this->fromConstantReflection($const);
			}
		}
		$class->setConstants($consts);
		$class->setCases($cases);

		return $class;
	}


	public function fromMethodReflection(\ReflectionMethod $from): Method
	{
		$method = new Method($from->name);
		$method->setParameters(array_map([$this, 'fromParameterReflection'], $from->getParameters()));
		$method->setStatic($from->isStatic());
		$isInterface = $from->getDeclaringClass()->isInterface();
		$method->setVisibility($isInterface ? null : $this->getVisibility($from));
		$method->setFinal($from->isFinal());
		$method->setAbstract($from->isAbstract() && !$isInterface);
		$method->setBody($from->isAbstract() ? null : '');
		$method->setReturnReference($from->returnsReference());
		$method->setVariadic($from->isVariadic());
		$method->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		$method->setAttributes(self::getAttributes($from));
		if ($from->getReturnType() instanceof \ReflectionNamedType) {
			$method->setReturnType($from->getReturnType()->getName());
			$method->setReturnNullable($from->getReturnType()->allowsNull());
		} elseif (
			$from->getReturnType() instanceof \ReflectionUnionType
			|| $from->getReturnType() instanceof \ReflectionIntersectionType
		) {
			$method->setReturnType((string) $from->getReturnType());
		}
		return $method;
	}


	/** @return GlobalFunction|Closure */
	public function fromFunctionReflection(\ReflectionFunction $from, bool $withBody = false)
	{
		$function = $from->isClosure() ? new Closure : new GlobalFunction($from->name);
		$function->setParameters(array_map([$this, 'fromParameterReflection'], $from->getParameters()));
		$function->setReturnReference($from->returnsReference());
		$function->setVariadic($from->isVariadic());
		if (!$from->isClosure()) {
			$function->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		}
		$function->setAttributes(self::getAttributes($from));
		if ($from->getReturnType() instanceof \ReflectionNamedType) {
			$function->setReturnType($from->getReturnType()->getName());
			$function->setReturnNullable($from->getReturnType()->allowsNull());
		} elseif (
			$from->getReturnType() instanceof \ReflectionUnionType
			|| $from->getReturnType() instanceof \ReflectionIntersectionType
		) {
			$function->setReturnType((string) $from->getReturnType());
		}
		if ($withBody) {
			if ($from->isClosure()) {
				throw new Nette\NotSupportedException('The $withBody parameter cannot be used for closures.');
			}
			$function->setBody($this->getExtractor($from)->extractFunctionBody($from->name));
		}
		return $function;
	}


	/** @return Method|GlobalFunction|Closure */
	public function fromCallable(callable $from)
	{
		$ref = Nette\Utils\Callback::toReflection($from);
		return $ref instanceof \ReflectionMethod
			? self::fromMethodReflection($ref)
			: self::fromFunctionReflection($ref);
	}


	public function fromParameterReflection(\ReflectionParameter $from): Parameter
	{
		$param = PHP_VERSION_ID >= 80000 && $from->isPromoted()
			? new PromotedParameter($from->name)
			: new Parameter($from->name);
		$param->setReference($from->isPassedByReference());
		if ($from->getType() instanceof \ReflectionNamedType) {
			$param->setType($from->getType()->getName());
			$param->setNullable($from->getType()->allowsNull());
		} elseif (
			$from->getType() instanceof \ReflectionUnionType
			|| $from->getType() instanceof \ReflectionIntersectionType
		) {
			$param->setType((string) $from->getType());
		}
		if ($from->isDefaultValueAvailable()) {
			$param->setDefaultValue($from->isDefaultValueConstant()
				? new Literal($from->getDefaultValueConstantName())
				: $from->getDefaultValue());
			$param->setNullable($param->isNullable() && $param->getDefaultValue() !== null);
		}
		$param->setAttributes(self::getAttributes($from));
		return $param;
	}


	public function fromConstantReflection(\ReflectionClassConstant $from): Constant
	{
		$const = new Constant($from->name);
		$const->setValue($from->getValue());
		$const->setVisibility($this->getVisibility($from));
		$const->setFinal(PHP_VERSION_ID >= 80100 ? $from->isFinal() : false);
		$const->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		$const->setAttributes(self::getAttributes($from));
		return $const;
	}


	public function fromCaseReflection(\ReflectionClassConstant $from): EnumCase
	{
		$const = new EnumCase($from->name);
		$const->setValue($from->getValue()->value ?? null);
		$const->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		$const->setAttributes(self::getAttributes($from));
		return $const;
	}


	public function fromPropertyReflection(\ReflectionProperty $from): Property
	{
		$defaults = $from->getDeclaringClass()->getDefaultProperties();
		$prop = new Property($from->name);
		$prop->setValue($defaults[$prop->getName()] ?? null);
		$prop->setStatic($from->isStatic());
		$prop->setVisibility($this->getVisibility($from));
		if (PHP_VERSION_ID >= 70400) {
			if ($from->getType() instanceof \ReflectionNamedType) {
				$prop->setType($from->getType()->getName());
				$prop->setNullable($from->getType()->allowsNull());
			} elseif (
				$from->getType() instanceof \ReflectionUnionType
				|| $from->getType() instanceof \ReflectionIntersectionType
			) {
				$prop->setType((string) $from->getType());
			}
			$prop->setInitialized($from->hasType() && array_key_exists($prop->getName(), $defaults));
			$prop->setReadOnly(PHP_VERSION_ID >= 80100 ? $from->isReadOnly() : false);
		} else {
			$prop->setInitialized(false);
		}
		$prop->setComment(Helpers::unformatDocComment((string) $from->getDocComment()));
		$prop->setAttributes(self::getAttributes($from));
		return $prop;
	}


	private function getAttributes($from): array
	{
		if (PHP_VERSION_ID < 80000) {
			return [];
		}
		return array_map(function ($attr) {
			return new Attribute($attr->getName(), $attr->getArguments());
		}, $from->getAttributes());
	}


	private function getVisibility($from): string
	{
		return $from->isPrivate()
			? ClassType::VISIBILITY_PRIVATE
			: ($from->isProtected() ? ClassType::VISIBILITY_PROTECTED : ClassType::VISIBILITY_PUBLIC);
	}


	private function getExtractor($from): Extractor
	{
		$file = $from->getFileName();
		if (!$file) {
			throw new Nette\InvalidStateException("Source code of $from->name not found.");
		}
		return new Extractor(file_get_contents($file));
	}
}

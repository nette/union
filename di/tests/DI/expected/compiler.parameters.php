%A%
class Container extends Nette\DI\Container
{
%A%

	public function __construct(array $params = [])
	{
		parent::__construct($params);
	}


	public function createService01(): Service
	{
		return new Service(
			123,
			trim(' a '),
			$this->getParameter('dynamic'),
			$this->getParameter('dynamic')['foo'],
			['expr' => trim(' a ')],
			trim(' a '),
			['dynamic' => $this->getParameter('dynamic'), 'inner' => $this->getParameter('dynamic')['foo']],
			$this->getParameter('dynamic'),
			$this->getParameter('dynamic')['foo'],
		);
	}

%A%

	protected function getStaticParameters(): array
	{
		return ['static' => 123, 'refStatic' => 123];
	}


	protected function getDynamicParameter(string|int $key): mixed
	{
		return match($key) {
			'dynamic' => 123,
			'dynamicArray' => [
			'dynamic' => $this->getParameter('dynamic'),
			'inner' => $this->getParameter('dynamic')['foo'],
			'expr' => trim(' a '),
		],
			'arrayDynamic' => ['dynamic' => $this->getParameter('dynamic'), 'inner' => $this->getParameter('dynamic')['foo']],
			'refDynamic' => $this->getParameter('dynamic'),
			'refDynamic2' => $this->getParameter('dynamic')['foo'],
			'refArrayD1' => ['dynamic' => $this->getParameter('dynamic'), 'inner' => $this->getParameter('dynamic')['foo']],
			'refArrayD2' => $this->getParameter('dynamic'),
			'refArrayD3' => $this->getParameter('dynamic')['foo'],
			default => parent::getDynamicParameter($key),
		};
	}


	public function getParameters(): array
	{
		array_map([$this, 'getParameter'], [
			'dynamic',
			'dynamicArray',
			'arrayDynamic',
			'refDynamic',
			'refDynamic2',
			'refArrayD1',
			'refArrayD2',
			'refArrayD3',
		]);
		return parent::getParameters();
	}
}

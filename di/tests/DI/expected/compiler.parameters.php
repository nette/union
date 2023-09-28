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
		switch (true) {
			case $key === 'dynamic': return 123;
			case $key === 'dynamicArray': return [
			'dynamic' => $this->getParameter('dynamic'),
			'inner' => $this->getParameter('dynamic')['foo'],
			'expr' => trim(' a '),
		];
			case $key === 'arrayDynamic': return [
			'dynamic' => $this->getParameter('dynamic'),
			'inner' => $this->getParameter('dynamic')['foo'],
		];
			case $key === 'refDynamic': return $this->getParameter('dynamic');
			case $key === 'refDynamic2': return $this->getParameter('dynamic')['foo'];
			case $key === 'refArrayD1': return [
			'dynamic' => $this->getParameter('dynamic'),
			'inner' => $this->getParameter('dynamic')['foo'],
		];
			case $key === 'refArrayD2': return $this->getParameter('dynamic');
			case $key === 'refArrayD3': return $this->getParameter('dynamic')['foo'];
			default: return parent::getDynamicParameter($key);
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

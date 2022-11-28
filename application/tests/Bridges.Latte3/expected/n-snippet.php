<?php
%A%
final class Template%a% extends Latte\Runtime\Template
{
	public const Blocks = [
		'snippet' => ['outer' => 'blockOuter', 'gallery' => 'blockGallery'],
	];


	public function main(array $ʟ_args): void
	{
%A%
		echo '	<div class="test"';
		echo ' id="', htmlspecialchars($this->global->snippetDriver->getHtmlId('outer')), '"';
		echo '>';
		$this->renderBlock('outer', [], null, 'snippet') /* line %d% */;
		echo '</div>

	<div';
		echo ' id="', htmlspecialchars($this->global->snippetDriver->getHtmlId('gallery')), '"';
		echo ' class="';
		echo LR\Filters::escapeHtmlAttr('class') /* line %d% */;
		echo '">';
		$this->renderBlock('gallery', [], null, 'snippet') /* line %d% */;
		echo '</div>
';
	}


	/** n:snippet="outer" on line %d% */
	public function blockOuter(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		$this->global->snippetDriver->enter('outer', 'static') /* line %d% */;
		try {
			echo '
	<p>Outer</p>
	';

		} finally {
			$this->global->snippetDriver->leave();
		}
	}


	/** n:snippet="gallery" on line %d% */
	public function blockGallery(array $ʟ_args): void
	{
		extract($this->params);
		extract($ʟ_args);
		unset($ʟ_args);

		$this->global->snippetDriver->enter('gallery', 'static') /* line %d% */;
		try {
		} finally {
			$this->global->snippetDriver->leave();
		}
	}
}

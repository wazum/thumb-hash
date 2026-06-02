<?php

declare(strict_types=1);

namespace Wazum\ThumbHash\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3Fluid\Fluid\View\TemplateView;

final class ThumbHashViewHelperRenderTest extends AbstractFunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'fluid',
    ];

    #[Test]
    public function viewHelperRendersGeneratedHashForFile(): void
    {
        $file = $this->provideIndexedFile();

        $output = $this->render(
            '{namespace th=Wazum\ThumbHash\ViewHelpers}<th:thumbHash file="{file}" />',
            ['file' => $file],
        );

        self::assertNotSame('', $output, 'ViewHelper should render a non-empty hash');

        $stored = $this->readHash('sys_file_metadata', 'file', (int) $file->getUid());
        self::assertSame($stored, $output, 'Rendered output must match the stored hash');
    }

    #[Test]
    public function viewHelperRendersEmptyStringForUnsupportedObject(): void
    {
        $output = $this->render(
            '{namespace th=Wazum\ThumbHash\ViewHelpers}<th:thumbHash file="{file}" />',
            ['file' => new \stdClass()],
        );

        self::assertSame('', $output);
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function render(string $templateSource, array $variables): string
    {
        $renderingContext = $this->get(RenderingContextFactory::class)->create();
        $renderingContext->getTemplatePaths()->setTemplateSource($templateSource);

        $view = new TemplateView($renderingContext);
        $view->assignMultiple($variables);

        $rendered = $view->render();
        \assert(\is_string($rendered));

        return \trim($rendered);
    }
}

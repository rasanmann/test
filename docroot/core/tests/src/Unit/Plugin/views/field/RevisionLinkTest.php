<?php

namespace Drupal\Tests\node\Unit\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\node\Plugin\views\field\RevisionLink;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\node\Plugin\views\field\RevisionLink
 * @group node
 */
class RevisionLinkTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    $container = new ContainerBuilder();
    \Drupal::setContainer($container);
  }

  /**
   * Tests the render method.
   */
  public function testRender() {
    $row = new ResultRow();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->createMock(TranslationInterface::class));
    \Drupal::setContainer($container);
    $field = new RevisionLink([], '', [], $this->createMock(AccessManagerInterface::class), $this->createMock(EntityTypeManagerInterface::class), $this->createMock(EntityRepositoryInterface::class), $this->createMock(LanguageManagerInterface::class));
    $view = $this->createMock(ViewExecutable::class);
    $display = $this->createMock(DisplayPluginBase::class);
    $field->init($view, $display);
    $this->assertEquals('', $field->render($row));
  }

}

<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearchFilter;

use Drupal\Core\Plugin\PluginBase;
use Drupal\xtcsearch\PluginManager\XtcSearchFilterType\XtcSearchFilterTypePluginBase;

/**
 * Default class used for xtcsearch_filters plugins.
 */
class XtcsearchFilterDefault extends PluginBase implements XtcsearchFilterInterface
{

  /**
   * @var XtcSearchFilterTypePluginBase
   */
  protected $filter;

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->init();
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // The title from YAML file discovery may be a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  protected function init(){
    $types = \Drupal::service('plugin.manager.xtcsearch_filter_type');
    $this->filter = $types->createInstance($this->getPluginDefinition()['type']);
    $this->filter->setFilter($this);
  }

  public function getFilterType() : XtcSearchFilterTypePluginBase{
    return $this->filter;
  }

  public function getTitle() : string{
    return $this->getPluginDefinition()['title'] ?? '';
  }

  public function getFieldName() : string{
    return $this->getPluginDefinition()['fieldName'] ?? '';
  }

  public function getPlaceholder() : string{
    return $this->getPluginDefinition()['placeholder'] ?? '';
  }

  public function getParams() : array{
    return $this->getPluginDefinition()['params'] ?? [];
  }

}

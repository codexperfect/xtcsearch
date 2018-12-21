<?php

namespace Drupal\xtcsearch\PluginManager\XtcSearch;


use Drupal\Core\Plugin\PluginBase;
use Drupal\xtcsearch\Form\XtcSearchFormBase;

/**
 * Default class used for xtc_searchs plugins.
 */
class XtcSearchDefault extends PluginBase implements XtcSearchInterface
{

  /**
   * @var XtcSearchFormBase
   */
  protected $form;

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
    $this->form = $this->buildForm();
  }

  protected function buildForm() : XtcSearchFormBase{
    $definition = $this->getPluginDefinition();
    $definition['pluginId'] = $this->getPluginId();
    $formClass = $definition['form'] ?? 'Drupal\xtcsearch\Form\XtcSearchForm';
    return New $formClass($definition);
  }

  /**
   * @return \Drupal\xtcsearch\Form\XtcSearchFormBase
   */
  public function getForm(){
    return $this->form;
  }
}

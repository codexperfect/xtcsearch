<?php

namespace Drupal\xtcsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\xtc\XtendedContent\API\Config;
use Drupal\xtc\XtendedContent\API\XtcForm;
use Drupal\xtc\XtendedContent\API\XtcSearch;

/**
 * Class XtcSearchBlockBase
 *
 * @package Drupal\xtcsearch\Plugin\Block
 */
abstract class XtcSearchBlockBase extends BlockBase
{

  protected function getSearch() : array {
    return XtcSearch::get($this->getSearchName());
  }

  /**
   * @return array
   */
  public function build() {
    $search = XtcForm::load($this->getSearchName());
    $build = $this->getSearch();
    if($search['label'] instanceof TranslatableMarkup){
      $build['#title'] = $search['label']->getUntranslatedString();
      $build['#links'] = $this->getLinks($build);
    }
    return $build;
  }

  protected function getLinks($build){
    $links = [];
    foreach(['top_link', 'bottom_link'] as $name){
      if(!empty($build['xtc_links'][$name])){
        $links[$name] = [
          'label' => $build['xtc_links'][$name]['label'],
          'url' => $build['xtc_links'][$name]['url'],
        ];
      }
    }
    return $links;
  }

  abstract protected function getSearchName() : string;

}

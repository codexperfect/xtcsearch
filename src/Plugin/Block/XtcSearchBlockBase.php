<?php

namespace Drupal\xtcsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\xtc\XtendedContent\API\Config;

/**
 * Class XtcSearchBlockBase
 *
 * @package Drupal\xtcsearch\Plugin\Block
 */
abstract class XtcSearchBlockBase extends BlockBase
{

  protected function getSearch() : array {
    return Config::getSearch($this->getSearchName());
  }

  /**
   * @return array
   */
  public function build() {
    $search = Config::loadXtcForm($this->getSearchName());
    $build = $this->getSearch();
    if($search['label'] instanceof TranslatableMarkup){
      $build['#title'] = $search['label']->getUntranslatedString();
    }
    return $build;
  }

  abstract protected function getSearchName() : string;

}

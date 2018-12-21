<?php

namespace Drupal\xtcsearch\Plugin\Block;

use Drupal\Core\Block\BlockBase;
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
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->getSearch();
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->title = $title;
    return $this;
  }

  abstract protected function getSearchName() : string;

}

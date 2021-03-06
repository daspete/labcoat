<?php

namespace Labcoat\Mocks\Patterns;

use Labcoat\PatternLab;
use Labcoat\Patterns\PatternInterface;

class Pattern implements PatternInterface {

  public $file;
  public $id;
  public $name;
  public $normalizedPath;
  public $partial;
  public $path;
  public $slug;
  public $state;
  public $template;

  public function actsLikePattern() {
    return true;
  }

  public function actsLikeSection() {
    return false;
  }

  public function getNormalizedPath() {
    return $this->normalizedPath;
  }

  public function getSlug() {
    return $this->slug;
  }

  public function isPattern() {
    return true;
  }

  public function isPseudoPattern() {
    return false;
  }

  public function isSubtype() {
    return false;
  }

  public function isType() {
    return false;
  }

  public function getData() {
    // TODO: Implement getData() method.
  }

  public function getFile() {
    return $this->file;
  }

  public function getId() {
    return $this->id;
  }

  public function getIncludedPatterns() {
    // TODO: Implement getIncludedPatterns() method.
  }

  public function getName() {
    return $this->name;
  }

  public function getPartial() {
    return $this->partial;
  }

  public function getPath() {
    return $this->path;
  }

  public function getPseudoPatterns() {
    // TODO: Implement getPseudoPatterns() method.
  }

  public function getState() {
    return $this->state;
  }

  public function getTemplate() {
    return $this->template;
  }

  public function getTime() {
    // TODO: Implement getTime() method.
  }
}
<?php

namespace Labcoat\Mocks\PatternLab\Styleguide;

use Labcoat\PatternLab\Styleguide\Patterns\PatternInterface;
use Labcoat\PatternLab\Styleguide\Files\Html\PageInterface;
use Labcoat\PatternLab\Styleguide\StyleguideInterface;

class Styleguide implements StyleguideInterface {

  public $globalData = [];
  public $patternlab;
  public $patterns = [];
  public $rendered = [];
  public $renders = [];

  public function addPattern(\Labcoat\PatternLab\PatternInterface $pattern) {
    $this->patterns[] = $pattern;
  }

  public function getGlobalData() {
    return $this->globalData;
  }

  public function getPatternLab() {
    return $this->patternlab;
  }

  public function generate($directory) {
    // TODO: Implement generate() method.
  }

  public function getCacheBuster() {
    // TODO: Implement getCacheBuster() method.
  }

  public function getConfig() {
    // TODO: Implement getConfig() method.
  }

  public function getControls() {
    // TODO: Implement getControls() method.
  }

  public function getIndexPaths() {
    // TODO: Implement getIndexPaths() method.
  }

  public function getPattern($id) {
    return $this->patterns[$id];
  }

  public function getPatternData(PatternInterface $pattern) {
    // TODO: Implement getPatternData() method.
  }

  public function getPatternPaths() {
    // TODO: Implement getPatternPaths() method.
  }

  public function getPlugins() {
    // TODO: Implement getPlugins() method.
  }

  public function getTwig() {
    // TODO: Implement getTwig() method.
  }

  public function renderPattern(PatternInterface $pattern, array $data = []) {
    return $this->rendered[$pattern->getId()];
  }

  public function getMaximumWidth() {
    // TODO: Implement getMaximumWidth() method.
  }

  public function getMinimumWidth() {
    // TODO: Implement getMinimumWidth() method.
  }

  public function render($template, array $data = []) {
    $this->renders[] = [$template, $data];
    return $this->rendered[$template];
  }

  public function renderPage(PageInterface $page) {
    // TODO: Implement renderPage() method.
  }
}
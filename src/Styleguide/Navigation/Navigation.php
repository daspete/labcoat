<?php

namespace Labcoat\Styleguide\Navigation;

use Labcoat\ItemInterface;
use Labcoat\PatternLabInterface;
use Labcoat\Patterns\PatternInterface as SourcePattern;
use Labcoat\Sections\SubtypeInterface as SourceSubtype;
use Labcoat\Sections\TypeInterface as SourceType;

class Navigation implements \JsonSerializable {

  /**
   * @var array
   */
  protected $indexPaths = [];

  /**
   * @var array
   */
  protected $patternPaths = [];

  /**
   * @var Type[]
   */
  protected $types = [];

  public static function getTypeFromPath($path) {
    $segments = explode(DIRECTORY_SEPARATOR, $path);
    return array_shift($segments);
  }

  public static function escapePath($path) {
    return preg_replace('|[\\\/~]|', '-', $path);
  }

  public function __construct(PatternLabInterface $patternlab) {
    $items = new \RecursiveIteratorIterator($patternlab, \RecursiveIteratorIterator::SELF_FIRST);
    foreach ($items as $item) {
      if ($item->actsLikePattern()) $this->addPattern($item);
      elseif ($item->isSubtype()) $this->addSubtype($item);
      elseif ($item->isType()) $this->addType($item);
    }
  }

  public function addPattern(SourcePattern $pattern) {
    $type = $this->getTypeFromPath($pattern->getPath());
    $this->types[$type]->addPattern($pattern);
    $this->addPatternPath($pattern);
  }

  public function addSubtype(SourceSubtype $subtype) {
    $type = $this->getTypeFromPath($subtype->getPath());
    $this->types[$type]->addSubtype($subtype);
    $this->addSubtypeIndexPath($subtype);
  }

  public function addType(SourceType $type) {
    $name = $this->getTypeFromPath($type->getPath());
    $this->types[$name] = new Type($type);
    ksort($this->types);
  }

  public function getIndexPaths() {
    return $this->indexPaths;
  }

  public function getPatternPaths() {
    return $this->patternPaths;
  }

  public function jsonSerialize() {
    return [
      'patternTypes' => array_values($this->types),
    ];
  }

  /**
   * @param PatternInterface $pattern
   */
  protected function addPatternPath(SourcePattern $pattern) {
    $path = explode('/', $pattern->getNormalizedPath());
    $type = array_shift($path);
    $name = $this->escapePath(array_pop($path));
    $this->patternPaths[$type][$name] = $this->makeItemPath($pattern);
    ksort($this->patternPaths[$type]);
  }

  /**
   * @param SubtypeInterface $subtype
   */
  protected function addSubtypeIndexPath(SourceSubtype $subtype) {
    $names = explode('/', $subtype->getNormalizedPath());
    list($type, $name) = $names;
    if (!isset($this->indexPaths[$type])) {
      $segments = explode(DIRECTORY_SEPARATOR, $subtype->getPath());
      $typePath = array_shift($segments);
      $this->indexPaths[$type] = ['all' => $typePath];
      ksort($this->indexPaths);
    }
    $this->indexPaths[$type][$name] = $this->makeItemPath($subtype);
    ksort($this->indexPaths[$type]);
  }

  protected function makeItemPath(ItemInterface $item) {
    return $this->escapePath($item->getPath());
  }
}

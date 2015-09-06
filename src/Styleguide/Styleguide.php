<?php

namespace Labcoat\Styleguide;

use Labcoat\Assets\AssetDirectory;
use Labcoat\PatternLab;
use Labcoat\PatternLabInterface;
use Labcoat\Styleguide\Files\AnnotationsFile;
use Labcoat\Styleguide\Files\AssetFile;
use Labcoat\Styleguide\Files\DataFile;
use Labcoat\Styleguide\Files\FileInterface;
use Labcoat\Styleguide\Files\LatestChangeFile;
use Labcoat\Styleguide\Files\PageFile;
use Labcoat\Styleguide\Files\PatternSourceFile;
use Labcoat\Styleguide\Files\PatternTemplateFile;
use Labcoat\Styleguide\Files\StyleguideAssetFile;
use Labcoat\Styleguide\Pages\PatternPage;
use Labcoat\Styleguide\Pages\PatternPageInterface;
use Labcoat\Styleguide\Pages\StyleguideIndexPage;
use Labcoat\Styleguide\Pages\SubTypeIndexPage;
use Labcoat\Styleguide\Pages\TypeIndexPage;
use Labcoat\Styleguide\Patterns\Pattern;
use Labcoat\Styleguide\Patterns\PatternInterface;

class Styleguide implements StyleguideInterface {

  /**
   * @var int
   */
  protected $cacheBuster;

  /**
   * @var string
   */
  protected $destinationPath;

  /**
   * @var \Labcoat\Styleguide\Files\FileInterface[]
   */
  protected $files = [];

  /**
   * @var array|null
   */
  protected $globalData;

  /**
   * @var array
   */
  protected $lineages;

  /**
   * @var array
   */
  protected $patternData = [];

  /**
   * @var \Labcoat\PatternLabInterface
   */
  protected $patternlab;

  /**
   * @var \Labcoat\Styleguide\Patterns\PatternInterface[]
   */
  protected $patterns = [];

  /**
   * @var int
   */
  protected $time = 0;

  /**
   * @var \Twig_Environment
   */
  protected $twig;

  /**
   * @param PatternLabInterface $patternlab
   */
  public function __construct(PatternLabInterface $patternlab) {
    $this->patternlab = $patternlab;
    $this->cacheBuster = time();
    $this->makeFiles();
  }

  public function __toString() {
    $str = '';
    foreach ($this->files as $file) {
      $str .= $file->getPath() . "\n";
      $str .= '  ' . date('r', $file->getTime()) . "\n";
    }
    return $str;
  }

  public function generate($directory) {
    foreach ($this->files as $path => $file) {
      $destination = PatternLab::makePath([$directory, $path]);
      $this->ensurePathDirectory($destination);
      $file->put($this, $destination);
    }
  }

  /**
   * @return int
   */
  public function getCacheBuster() {
    return $this->cacheBuster;
  }

  public function getFiles() {
    return $this->files;
  }

  public function getGlobalData() {
    if (!isset($this->globalData)) $this->globalData = $this->patternlab->getGlobalData();
    return $this->globalData;
  }

  /**
   * @param $id
   * @return \Labcoat\Styleguide\Patterns\PatternInterface
   */
  public function getPattern($id) {
    return $this->patterns[$id];
  }

  public function getPatternLab() {
    return $this->patternlab;
  }

  /**
   * @return \Twig_Environment
   */
  public function getTwig() {
    if (!isset($this->twig)) $this->makeTwig();
    return $this->twig;
  }

  public function renderPattern(PatternInterface $pattern, array $data = []) {
    return $this->patternlab->render($pattern->getTemplate(), $data);
  }

  /**
   * @param FileInterface $file
   */
  protected function addFile(FileInterface $file) {
    $this->files[$file->getPath()] = $file;
  }

  /**
   * @param $path
   */
  protected function ensureDirectory($path) {
    if (!is_dir($path)) mkdir($path, 0777, true);
  }

  /**
   * @param $path
   */
  protected function ensurePathDirectory($path) {
    $this->ensureDirectory(dirname($path));
  }

  protected function findPatternLineages() {
    $this->lineages = [];
    $includes = [];
    foreach ($this->patterns as $id => $pattern) {
      foreach ($pattern->getIncludedPatterns() as $included) {
        if (!isset($includes[$included])) {
          $includedPattern = $this->patternlab->getPattern($included);
          $includes[$included] = $includedPattern->getId();
        }
        $includeId = $includes[$included];
        $pattern->addIncludedPattern($this->patterns[$includeId]);
        $this->patterns[$includeId]->addIncludingPattern($pattern);
      }
    }
    print_r($includes);
  }

  /**
   * @return string
   */
  protected function getPatternFooterTemplatePath() {
    return $this->patternlab->getStyleguideFooter();
  }

  /**
   * @return string
   */
  protected function getPatternHeaderTemplatePath() {
    return $this->patternlab->getStyleguideHeader();
  }

  /**
   * @param $template
   * @return string
   */
  protected function getStyleguideTemplateContent($template) {
    return file_get_contents($this->getStyleguideTemplatePath($template));
  }

  /**
   * @param $template
   * @return string
   */
  protected function getStyleguideTemplatePath($template) {
    return PatternLab::makePath([$this->patternlab->getStyleguideTemplatesDirectory(), $template]);
  }

  protected function makeAnnotationsFile() {
    if ($path = $this->patternlab->getAnnotationsFile()) {
      $this->addFile(new AnnotationsFile($path));
    }
  }

  protected function makeAssetFiles() {
    $this->makePatternLabAssetFiles();
    $this->makeStyleguideAssetFiles();
  }

  protected function makeDataFile() {
    $this->addFile(new DataFile($this->patternlab));
  }

  protected function makeFiles() {
    $this->makePageFiles($this->makePages());
    $this->makeAssetFiles();
    $this->makeDataFile();
    $this->makeAnnotationsFile();
    $this->makeLatestChangeFile();
  }

  protected function makeLatestChangeFile() {
    $this->addFile(new LatestChangeFile(time()));
  }

  protected function makePageFiles(array $pages) {
    foreach ($pages as $page) {
      $this->addFile(new PageFile($page));
      if ($page instanceof PatternPageInterface) {
        $this->addFile(new PatternSourceFile($page->getPattern()));
        $this->addFile(new PatternTemplateFile($page->getPattern()));
      }
    }
  }

  protected function makePages() {
    /** @var \Labcoat\Styleguide\Pages\IndexPageInterface[] $pages */
    $pages = [];
    $index = new StyleguideIndexPage();
    $items = new \RecursiveIteratorIterator($this->patternlab, \RecursiveIteratorIterator::SELF_FIRST);
    foreach ($items as $item) {
      $id = $item->getId();
      if ($item->actsLikePattern()) {
        $this->patterns[$id] = new Pattern($this, $item);
        $pages[$id] = new PatternPage($this->patterns[$id]);
        $index->addPattern($this->patterns[$id]);
        $path = dirname($item->getPath());
        while ($path && $path != '.') {
          if (!isset($pages[$path])) break;
          $pages[$path]->addPattern($this->patterns[$id]);
          $path = dirname($path);
        }
      }
      elseif ($item->isType()) {
        $pages[$id] = new TypeIndexPage($item);
      }
      elseif ($item->isSubtype()) {
        $pages[$id] = new SubTypeIndexPage($item);
      }
    }
    $this->findPatternLineages();
    $pages[] = $index;
    $this->time = $index->getTime();
    return $pages;
  }

  protected function makePatternLabAssetFiles() {
    foreach ($this->patternlab->getAssets() as $asset) {
      $this->addFile(new AssetFile($asset));
    }
  }

  protected function makeStyleguideAssetFiles() {
    $dir = $this->patternlab->getStyleguideAssetsDirectory();
    $assets = new AssetDirectory($this->patternlab, $dir);
    foreach ($assets->getAssets() as $asset) {
      $this->addFile(new StyleguideAssetFile($asset));
    }
  }

  protected function makeTwig() {
    $templates = [
      'partials/general-footer' => $this->getStyleguideTemplateContent('partials/general-footer.twig'),
      'partials/general-header' => $this->getStyleguideTemplateContent('partials/general-header.twig'),
      'patternSection.twig' => $this->getStyleguideTemplateContent('partials/patternSection.twig'),
      'patternSectionSubtype.twig' => $this->getStyleguideTemplateContent('partials/patternSectionSubtype.twig'),
      'viewall' => $this->getStyleguideTemplateContent('viewall.twig'),
    ];
    $templates['patternLabHead'] = file_get_contents($this->getPatternHeaderTemplatePath());
    $templates['patternLabFoot'] = file_get_contents($this->getPatternFooterTemplatePath());
    $loader = new \Twig_Loader_Array($templates);
    $this->twig = new \Twig_Environment($loader, ['cache' => false]);
  }

  /**
   * @param $path
   * @param $content
   */
  protected function writeFile($path, $content) {
    $this->ensurePathDirectory($path);
    file_put_contents($path, $content);
  }
}
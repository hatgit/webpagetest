<?php

require_once __DIR__ . '/FileHandler.php';
require_once __DIR__ . '/../devtools.inc.php';
require_once __DIR__ . '/../common_lib.inc';
require_once __DIR__ . '/../domains.inc';
require_once __DIR__ . '/../breakdown.inc';

class TestRunResult {

  /**
   * @var TestInfo
   */
  private $testInfo;
  /**
   * @var FileHandler
   */
  private $fileHandler;
  private $rawData;
  private $run;
  private $cached;
  private $localPaths;

  private function __construct($testInfo, &$pageData, $run, $cached, $fileHandler = null) {
    // This isn't likely to stay the standard constructor, so we name it explicitly as a static function below
    $this->testInfo = $testInfo;
    $this->rawData = &$pageData;
    $this->run = intval($run);
    $this->cached = $cached ? true : false;
    $this->fileHandler = $fileHandler ? $fileHandler : new FileHandler();
    $this->localPaths = new TestPaths($this->testInfo->getRootDirectory(), $this->run, $this->cached);
  }

  /**
   * Creates a TestResult instance from a pageResults array with all data about a run
   * @param TestInfo $testInfo Related test information
   * @param array $pageData The pageData array with test results
   * @param int $run The run to return the data for
   * @param bool $cached False for first view, true for repeat view
   * @return TestRunResult The created instance
   */
  public static function fromPageData($testInfo, &$pageData, $run, $cached) {
    return new self($testInfo, $pageData, $run, $cached);
  }

  public function getUrlGenerator($baseUrl, $friendly = true) {
    return UrlGenerator::create($friendly, $baseUrl, $this->testInfo->getRootDirectory(), $this->run, $this->cached);
  }

  /**
   * @return int The run number
   */
  public function getRunNumber() {
    return $this->run;
  }

  /**
   * @return boolean False if first view, true if repeat view
   */
  public function isCachedRun() {
    return $this->cached;
  }

  /**
   * @return array Raw result data
   */
  public function getRawResults() {
    return $this->rawData;
  }

  /**
   * @return string The score
   */
  public function getPageSpeedScore() {
    // TODO: move implementation to this method
    if ($this->fileHandler->gzFileExists($this->localPaths->pageSpeedFile())) {
      return GetPageSpeedScore($this->localPaths->pageSpeedFile());
    }
  }

  public function getVisualProgress() {
    // TODO: move implementation to this method
    return GetVisualProgressForStep($this->localPaths, $this->testInfo->isRunComplete($this->run), null, null,
      $this->getStartOffset());
  }

  public function getRequests() {
    // TODO: move implementation to this method
    return getRequestsForStep($this->localPaths, $this->getUrlGenerator(""), $secure, $haveLocations, false, true);
  }

  public function getDomainBreakdown() {
    // TODO: move implementation to this method
    return getDomainBreakdownForRequests($this->getRequests());
  }

  public function getMimeTypeBreakdown() {
    // TODO: move implementation to this method
    $requests = null;
    return getBreakdownForStep($this->localPaths, $this->getUrlGenerator(""), $requests);
  }

  public function getConsoleLog() {
    // TODO: move implementation to this method, or encapsulate in another object
    return DevToolsGetConsoleLogForStep($this->localPaths);
  }

  /**
   * Gets the status messages for this run
   * @return array An array with array("time" => <timestamp>, "message" => <the actual Message>) for each message, or null
   */
  public function getStatusMessages() {
    $statusFile = $this->localPaths->statusFile();
    if (!$this->fileHandler->gzFileExists($statusFile)) {
      return null;
    }

    $statusMessages = array();
    foreach($this->fileHandler->gzReadFile($statusFile) as $line) {
      $line = trim($line);
      if (!strlen($line)) {
        continue;
      }
      $parts = explode("\t", $line);
      $statusMessages[] = array("time" => $parts[0], "message" => $parts[1]);
    }
    return $statusMessages;
  }


  private function getStartOffset() {
    if (!array_key_exists('testStartOffset', $this->rawData)) {
      return 0;
    }
    return intval(round($this->rawData['testStartOffset']));
  }
}
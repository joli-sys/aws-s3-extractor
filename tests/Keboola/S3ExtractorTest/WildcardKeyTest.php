<?php
namespace Keboola\S3ExtractorTest;

use Keboola\S3Extractor\Extractor;
use Monolog\Handler\TestHandler;
use Monolog\Logger;

class WildcardKeyTest extends \PHPUnit_Framework_TestCase
{
    const AWS_S3_BUCKET_ENV = 'AWS_S3_BUCKET';
    const AWS_S3_ACCESS_KEY_ENV = 'TESTS_AWS_ACCESS_KEY';
    const AWS_S3_SECRET_KEY_ENV = 'TESTS_AWS_SECRET_KEY';

    protected $path = '/tmp/wildcard';

    public function setUp()
    {
        if (!file_exists($this->path)) {
            mkdir($this->path);
        }
    }

    public function tearDown()
    {
        passthru('rm -rf ' . $this->path);
    }

    /**
     * @dataProvider initialForwardSlashProvider
     */
    public function testSuccessfulDownloadFromRoot($initialForwardSlash)
    {
        $key = "f*";
        if ($initialForwardSlash) {
            $key = "/" . $key;
        }
        $testHandler = new TestHandler();
        $extractor = new Extractor([
            "accessKeyId" => getenv(self::AWS_S3_ACCESS_KEY_ENV),
            "#secretAccessKey" => getenv(self::AWS_S3_SECRET_KEY_ENV),
            "bucket" => getenv(self::AWS_S3_BUCKET_ENV),
            "key" => $key
        ], (new Logger('test'))->pushHandler($testHandler));
        $extractor->extract($this->path);

        $expectedFile = $this->path . '/file1.csv';
        $this->assertFileExists($expectedFile);
        $this->assertFileEquals(__DIR__ . "/../../_data/file1.csv", $expectedFile);

        $this->assertFileNotExists($this->path . '/file2.csv');
        $this->assertFileNotExists($this->path . '/folder1/file1.csv');
        $this->assertFileNotExists($this->path . '/folder2/file1.csv');
        $this->assertFileNotExists($this->path . '/folder2/file2.csv');
        $this->assertTrue($testHandler->hasInfo("Downloading file /file1.csv"));
        $this->assertCount(1, $testHandler->getRecords());
    }

    /**
     * @dataProvider initialForwardSlashProvider
     */
    public function testSuccessfulDownloadFromFolder($initialForwardSlash)
    {
        $key = "folder2/*";
        if ($initialForwardSlash) {
            $key = "/" . $key;
        }
        $testHandler = new TestHandler();
        $extractor = new Extractor([
            "accessKeyId" => getenv(self::AWS_S3_ACCESS_KEY_ENV),
            "#secretAccessKey" => getenv(self::AWS_S3_SECRET_KEY_ENV),
            "bucket" => getenv(self::AWS_S3_BUCKET_ENV),
            "key" => $key
        ], (new Logger('test'))->pushHandler($testHandler));
        $extractor->extract($this->path);

        $expectedFile = $this->path . '/file1.csv';
        $this->assertFileExists($expectedFile);
        $this->assertFileEquals(__DIR__ . "/../../_data/folder2/file1.csv", $expectedFile);

        $expectedFile = $this->path . '/file2.csv';
        $this->assertFileExists($expectedFile);
        $this->assertFileEquals(__DIR__ . "/../../_data/folder2/file2.csv", $expectedFile);

        $this->assertTrue($testHandler->hasInfo("Downloading file /folder2/file1.csv"));
        $this->assertTrue($testHandler->hasInfo("Downloading file /folder2/file1.csv"));
        $this->assertCount(2, $testHandler->getRecords());
    }
}

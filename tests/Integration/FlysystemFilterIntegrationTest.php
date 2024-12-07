<?php
namespace Marktaborosi\FlysystemFilter\Tests\Integration;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemException;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Marktaborosi\FlysystemFilter\FilterBuilder;
use Marktaborosi\FlysystemFilter\FlysystemFilter;
use PHPUnit\Framework\TestCase;

class FlysystemFilterIntegrationTest extends TestCase
{
    private Filesystem $filesystem;
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = __DIR__ . '/../Storage';

        // Create a local filesystem adapter pointing to the test directory
        $adapter = new LocalFilesystemAdapter($this->testDirectory);
        $this->filesystem = new Filesystem($adapter);
    }

    /**
     * Test filtering to retrieve only files from the filesystem.
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_files_only(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('', true);

        // Create a filter to match only files
        $filter = new FilterBuilder();
        $filter->isFile();

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only the files are returned
        $this->assertCount(4, $filteredPaths);
        $this->assertContains('another-test-notes.txt', $filteredPaths);
        $this->assertContains('console.log', $filteredPaths);
        $this->assertContains('dummy.jpg', $filteredPaths);
        $this->assertContains('test-notes.txt', $filteredPaths);
    }

    /**
     * Test filtering to retrieve files by extension (e.g., .txt files).
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_by_extension(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('', true);

        // Create a filter to match only .txt files
        $filter = new FilterBuilder();
        $filter->extensionEquals('txt');

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only .txt files are returned
        $this->assertCount(2, $filteredPaths);
        $this->assertContains('another-test-notes.txt', $filteredPaths);
        $this->assertContains('test-notes.txt', $filteredPaths);
    }

    /**
     * Test filtering files whose filename contains specific substrings.
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_by_filename_contains(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('', true);

        // Create a filter to match only .txt files
        $filter = new FilterBuilder();
        $filter->filenameContains(['conso','dum']);

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only .txt files are returned
        $this->assertCount(2, $filteredPaths);
        $this->assertContains('console.log', $filteredPaths);
        $this->assertContains('dummy.jpg', $filteredPaths);
    }

    /**
     * Test filtering files whose filename equals specific values.
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_by_filename_equals(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('', true);

        // Create a filter to match only .txt files
        $filter = new FilterBuilder();
        $filter->filenameEquals(['console','dummy']);

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only .txt files are returned
        $this->assertCount(2, $filteredPaths);
        $this->assertContains('console.log', $filteredPaths);
        $this->assertContains('dummy.jpg', $filteredPaths);
    }

    /**
     * Test filtering files whose filename does not equal specific values.
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_by_filename_not_equals(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('', true);

        // Create a filter to match only .txt files
        $filter = new FilterBuilder();
        $filter
            ->isFile()
            ->filenameNotEquals(['console','dummy']);

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only .txt files are returned
        $this->assertCount(2, $filteredPaths);
        $this->assertContains('another-test-notes.txt', $filteredPaths);
        $this->assertContains('test-notes.txt', $filteredPaths);
    }

    /**
     * Test filtering files by their exact basename (filename with extension).
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_by_basename_1(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('', true);

        // Create a filter to match only .txt files
        $filter = new FilterBuilder();
        $filter
            ->isFile()
            ->basenameEquals(['console.log','dummy.jpg']);

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only .txt files are returned
        $this->assertCount(2, $filteredPaths);
        $this->assertContains('console.log', $filteredPaths);
        $this->assertContains('dummy.jpg', $filteredPaths);
    }


    /**
     * Test filtering files using logical expressions with AND, OR, and grouping.
     *
     * @return void
     * @throws FilesystemException
     */
    public function test_filtering_with_logical_expressions(): void
    {
        // Retrieve contents from the filesystem
        $flysystemContents = $this->filesystem->listContents('');

        // Create a filter to match only .txt files
        $filter = new FilterBuilder();
        $filter
            ->isFile()
            ->and()
            ->group_start()
            ->filenameContains('con')
            ->or()
            ->filenameContains('dum')
            ->group_end();

        // Apply the filter
        $filteredResults = FlysystemFilter::filter($flysystemContents, $filter);

        // Extract paths from the filtered results for assertions
        $filteredPaths = [];
        foreach ($filteredResults as $result) {
            $filteredPaths[] = $result->path();
        }

        // Assert that only .txt files are returned
        $this->assertCount(2, $filteredPaths);
        $this->assertContains('console.log', $filteredPaths);
        $this->assertContains('dummy.jpg', $filteredPaths);
    }
}

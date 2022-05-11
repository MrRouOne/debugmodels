<?php /** @noinspection PhpMissingFieldTypeInspection */

namespace App\Providers;

use Egal\Model\ModelManager;
use Illuminate\Support\ServiceProvider;

class DebugModelsServiceProvider extends ServiceProvider
{
    public array $class;
    public bool $debugMode;
    public string $root;

    public function __construct($app)
    {
        parent::__construct($app);
        $this->setRoot();
        $this->setDebugModel();
        $this->scanModels($this->root);
    }

    protected function setRoot()
    {
        $this->root = config('debug')['root'];
    }

    protected function setDebugModel()
    {
        $this->debugMode = config('debug')['include'];
    }

    protected function scanModels(?string $dir = null): void
    {
        $baseDir = base_path($this->root);

        $dir === null ?: $dir = $baseDir;

        $modelsNamespace = str_replace('/', '\\', $this->root);
        $modelsNamespace[0] = strtoupper($modelsNamespace[0]);

        foreach (scandir($dir) as $dirItem) {
            $itemPath = str_replace('//', '/', $dir . '/' . $dirItem);

            if ($dirItem === '.' || $dirItem === '..') {
                continue;
            }

            if (is_dir($itemPath)) {
                $this->scanModels($itemPath);
            }

            if (!str_contains($dirItem, '.php')) {
                continue;
            }

            $classShortName = str_replace('.php', '', $dirItem);
            if (!preg_match("/^[a-z]+(Debug)$/i", $classShortName)) {
                continue;
            }

            $class = str_replace($dir, '', $itemPath);
            $class = str_replace($dirItem, $classShortName, $class);
            $class = str_replace('/', '\\', $class);

            $this->class[] = $modelsNamespace . $class;
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->debugMode) {
            return;
        }

        for ($i = 0; $i < count($this->class); $i++) {
            ModelManager::loadModel($this->class[$i]);
        }
    }
}

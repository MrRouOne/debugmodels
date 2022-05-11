<?php /** @noinspection PhpMissingFieldTypeInspection */

namespace MrRouOne\DebugModels;

use Egal\Model\ModelManager;
use Illuminate\Support\ServiceProvider;

class DebugModelsServiceProvider extends ServiceProvider
{
    public array $class;

    public function __construct($app)
    {
        parent::__construct($app);
        config(['app.debug_model_root' => 'app/DebugModels/']);
        config(['app.debug_model_include' => true]);
        $this->scanModels(config('app.debug_model_root', 'app/DebugModels/'));
    }

    protected function scanModels(?string $dir = null): void
    {
        $baseDir = base_path(config('app.debug_model_root', 'app/DebugModels/'));

        $dir === null ?: $dir = $baseDir;

        $modelsNamespace = str_replace('/', '\\', config('app.debug_model_root', 'app/DebugModels/'));
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
        if (!config('app.debug_model_include', false)) {
            return;
        }

        for ($i = 0; $i < count($this->class); $i++) {
            ModelManager::loadModel($this->class[$i]);
        }
    }
}

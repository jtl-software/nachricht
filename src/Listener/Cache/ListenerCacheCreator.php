<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/10
 */

namespace JTL\Nachricht\Listener\Cache;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Config\ConfigCache;

class ListenerCacheCreator
{
    /**
     * @param string $cacheFile
     * @param array $lookupPathList
     * @param bool $isDevelopment
     * @return ListenerCache
     */
    public function create(string $cacheFile, array $lookupPathList, bool $isDevelopment): ListenerCache
    {
        $configCache = new ConfigCache(
            $cacheFile,
            $isDevelopment
        );

        if (!$configCache->isFresh()) {
            $eventToListenerMap = [];

            $parserFactory = new ParserFactory();
            $nameResolver = new NameResolver();

            $parser = $parserFactory->create(ParserFactory::ONLY_PHP7);

            $files = $this->loadPhpFilesFromPathList($lookupPathList);

            foreach ($files as $file) {
                $listenerDetector = new ListenerDetector();
                $nodeTraverser = new NodeTraverser();
                $nodeTraverser->addVisitor($nameResolver);
                $nodeTraverser->addVisitor($listenerDetector);

                $phpCode = file_get_contents($file);

                $ast = $parser->parse($phpCode);
                $nodeTraverser->traverse($ast);

                if (!$listenerDetector->isClassListener()) {
                    continue;
                }

                $this->mapListenerToEvent(
                    $listenerDetector->getListenerClass(),
                    $listenerDetector->getListenerMethods(),
                    $eventToListenerMap
                );
            }

            $map = var_export($eventToListenerMap, true);
            $configCache->write("<?php\nreturn {$map};");
        }

        return new ListenerCache(require $cacheFile);
    }

    /**
     * @param array $lookupPathList
     * @return array
     */
    private function loadPhpFilesFromPathList(array $lookupPathList): array
    {
        $files = [];

        foreach ($lookupPathList as $lookupPath) {
            $files = array_merge($files, $this->recursivePhpFileSearch($lookupPath));
        }

        return $files;
    }

    /**
     * @param string $path
     * @return array
     */
    private function recursivePhpFileSearch(string $path): array
    {
        $pattern = $path . '/*.php';
        $files = glob($pattern);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $directory) {
            $files = array_merge($files, $this->recursivePhpFileSearch($directory));
        }

        return $files;
    }

    /**
     * @param string $listenerClass
     * @param array $listenerList
     * @param array $eventToListenerMap
     */
    private function mapListenerToEvent(string $listenerClass, array $listenerList, array &$eventToListenerMap): void
    {
        foreach ($listenerList as $listenerFunction) {
            $eventToListenerMap[$listenerFunction['eventClass']][] = [
                'listenerClass' => $listenerClass,
                'method' => $listenerFunction['methodName']
            ];
        }
    }
}

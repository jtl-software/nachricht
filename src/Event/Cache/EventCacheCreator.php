<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/10
 */

namespace JTL\Nachricht\Event\Cache;

use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Config\ConfigCache;

class EventCacheCreator
{
    /**
     * @param string $cacheFile
     * @param array $lookupPathList
     * @param bool $isDevelopment
     * @return EventCache
     */
    public function create(string $cacheFile, array $lookupPathList, bool $isDevelopment): EventCache
    {
        $configCache = new ConfigCache(
            $cacheFile,
            $isDevelopment
        );

        $cacheFileLoader = new EventCacheFileLoader();

        if (!$configCache->isFresh()) {
            $this->rebuildCache($lookupPathList, $configCache);
        }

        return new EventCache($cacheFileLoader->load($cacheFile));
    }


    /**
     * @param array $lookupPathList
     * @param ConfigCache $configCache
     * @return void
     */
    private function rebuildCache(array $lookupPathList, ConfigCache $configCache): void
    {
        $eventMap = [];

        $parserFactory = new ParserFactory();
        $nameResolver = new NameResolver();

        $parser = $parserFactory->create(ParserFactory::ONLY_PHP7);

        $files = $this->loadPhpFilesFromPathList($lookupPathList);

        foreach ($files as $file) {
            $listenerDetector = new ListenerDetector();
            $eventRoutingKeyExtractor = new AmqpEventRoutingKeyExtractor();

            $nodeTraverser = new NodeTraverser();
            $nodeTraverser->addVisitor($nameResolver);
            $nodeTraverser->addVisitor($listenerDetector);
            $nodeTraverser->addVisitor($eventRoutingKeyExtractor);

            $phpCode = file_get_contents($file);

            if ($phpCode === false) {
                continue;
            }
            $ast = $parser->parse($phpCode);

            if ($ast === null) {
                continue;
            }

            $nodeTraverser->traverse($ast);

            if ($listenerDetector->isClassListener() && $listenerDetector->getListenerClass() !== null) {
                $this->mapListenerToEvent(
                    $listenerDetector->getListenerClass(),
                    $listenerDetector->getListenerMethods(),
                    $eventMap
                );
            }

            if ($eventRoutingKeyExtractor->isClassEvent() && $eventRoutingKeyExtractor->getEventClass() !== null) {
                $this->mapRoutingKeyToEvent(
                    $eventRoutingKeyExtractor->getEventClass(),
                    $eventRoutingKeyExtractor->getRoutingKey(),
                    $eventMap
                );
            }
        }

        $map = var_export($eventMap, true);
        $configCache->write("<?php\nreturn {$map};");
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

        if ($files === false) {
            return [];
        }

        $directoryList = glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT);
        if ($directoryList === false) {
            return $files;
        }
        
        foreach ($directoryList as $directory) {
            $files = array_merge($files, $this->recursivePhpFileSearch($directory));
        }

        return $files;
    }

    /**
     * @param string $listenerClass
     * @param array $listenerList
     * @param array $eventMap
     */
    private function mapListenerToEvent(string $listenerClass, array $listenerList, array &$eventMap): void
    {
        foreach ($listenerList as $listenerFunction) {
            $eventMap[$listenerFunction['eventClass']]['listenerList'][] = [
                'listenerClass' => $listenerClass,
                'method' => $listenerFunction['methodName']
            ];
        }
    }

    private function mapRoutingKeyToEvent(string $eventClass, string $routingKey, array &$eventMap): void
    {
        $eventMap[$eventClass]['routingKey'] = AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey;
    }
}

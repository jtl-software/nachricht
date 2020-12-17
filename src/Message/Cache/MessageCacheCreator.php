<?php declare(strict_types=1);
/**
 * This File is part of JTL-Software
 *
 * User: pkanngiesser
 * Date: 2019/09/10
 */

namespace JTL\Nachricht\Message\Cache;

use JTL\Nachricht\Transport\Amqp\AmqpTransport;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Symfony\Component\Config\ConfigCache;

class MessageCacheCreator
{
    /**
     * @param string $cacheFile
     * @param array<string> $lookupPathList
     * @param bool $isDevelopment
     * @return MessageCache
     */
    public function create(string $cacheFile, array $lookupPathList, bool $isDevelopment): MessageCache
    {
        $configCache = new ConfigCache(
            $cacheFile,
            $isDevelopment
        );

        $cacheFileLoader = new MessageCacheFileLoader();

        if (!$configCache->isFresh()) {
            $this->rebuildCache($lookupPathList, $configCache);
        }

        return new MessageCache($cacheFileLoader->load($cacheFile));
    }


    /**
     * @param array<string> $lookupPathList
     * @param ConfigCache $configCache
     * @return void
     */
    private function rebuildCache(array $lookupPathList, ConfigCache $configCache): void
    {
        $messageMap = [];

        $parserFactory = new ParserFactory();
        $nameResolver = new NameResolver();

        $parser = $parserFactory->create(ParserFactory::ONLY_PHP7);

        $files = $this->loadPhpFilesFromPathList($lookupPathList);

        foreach ($files as $file) {
            $listenerDetector = new ListenerDetector();
            $messageRoutingKeyExtractor = new AmqpMessageRoutingKeyExtractor();

            $nodeTraverser = new NodeTraverser();
            $nodeTraverser->addVisitor($nameResolver);
            $nodeTraverser->addVisitor($listenerDetector);
            $nodeTraverser->addVisitor($messageRoutingKeyExtractor);

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
                $this->mapListenerToMessage(
                    $listenerDetector->getListenerClass(),
                    $listenerDetector->getListenerMethods(),
                    $messageMap
                );
            }

            if ($messageRoutingKeyExtractor->isClassMessage() && $messageRoutingKeyExtractor->getMessageClass() !== null) {
                $this->mapRoutingKeyToMessage(
                    $messageRoutingKeyExtractor->getMessageClass(),
                    $messageRoutingKeyExtractor->getRoutingKey(),
                    $messageMap
                );
            }
        }

        $map = var_export($messageMap, true);
        $configCache->write("<?php\nreturn {$map};");
    }

    /**
     * @param array<string> $lookupPathList
     * @return array<string>
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
     * @return array<string>
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
     * @param array<int, array{eventClass: string, methodName: string}> $listenerList
     * @param array<string, array{listenerList: array<int, array{listenerClass: string, method: string}>}> $messageMap
     */
    private function mapListenerToMessage(string $listenerClass, array $listenerList, array &$messageMap): void
    {
        foreach ($listenerList as $listenerFunction) {
            $messageMap[$listenerFunction['eventClass']]['listenerList'][] = [
                'listenerClass' => $listenerClass,
                'method' => $listenerFunction['methodName']
            ];
        }
    }

    /**
     * @param string $messageClass
     * @param string $routingKey
     * @param array<string, array{listenerList: array<int, array{listenerClass: string, method: string}>}> $messageMap
     */
    private function mapRoutingKeyToMessage(string $messageClass, string $routingKey, array &$messageMap): void
    {
        $messageMap[$messageClass]['routingKey'] = AmqpTransport::MESSAGE_QUEUE_PREFIX . $routingKey;
    }
}

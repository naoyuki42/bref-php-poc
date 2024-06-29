<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\InvalidLambdaEvent;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use Bref\Logger\StderrLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * S3のEventハンドラー
 */
class Handler extends S3Handler
{
    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param S3Event $event
     * @param Context $context
     *
     * @throws InvalidLambdaEvent
     */
    public function handleS3(S3Event $event, Context $context): void
    {
        $bucketName = $event->getRecords()[0]->getBucket()->getName();
        $fileName = $event->getRecords()[0]->getObject()->getKey();

        $this->logger->info($bucketName);
        $this->logger->warning($fileName);
    }
}

return new Handler(new StderrLogger(LogLevel::INFO));
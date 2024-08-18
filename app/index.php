<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Bref\Context\Context;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use Bref\Logger\StderrLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * S3のEventハンドラー
 */
final class Handler extends S3Handler
{
    /**
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param S3Event $event
     * @param Context $context
     */
    public function handleS3(S3Event $event, Context $context): void
    {
        $this->logger->info('=== START ===');

        $this->logger->info('success: invoke manual created lambda function and manual deploy zip lambda function');

        $this->logger->info('=== END ===');
    }
}

return new Handler(
    new StderrLogger(LogLevel::INFO),
);

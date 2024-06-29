<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Aws\Result;
use Aws\S3\S3Client;
use Bref\Context\Context;
use Bref\Event\InvalidLambdaEvent;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use Bref\Logger\StderrLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use ZBateson\MailMimeParser\MailMimeParser;

/**
 * S3のEventハンドラー
 */
class Handler extends S3Handler
{
    /**
     * @var string
     */
    private const BUCKET_NAME = 'bref-php-sample';

    /**
     * @var string
     */
    private const BUCKET_KEY_PREFIX = 'convert/';

    /**
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * @var MailMimeParser
     */
    private readonly MailMimeParser $mailParser;

    /**
     * @var S3Client
     */
    private readonly S3Client $s3Client;

    /**
     * @param LoggerInterface $logger
     * @param MailMimeParser $mailParser
     * @param S3Client $s3Client
     */
    public function __construct(LoggerInterface $logger, MailMimeParser $mailParser, S3Client $s3Client)
    {
        $this->logger     = $logger;
        $this->mailParser = $mailParser;
        $this->s3Client   = $s3Client;
    }

    /**
     * @param S3Event $event
     * @param Context $context
     *
     * @throws InvalidLambdaEvent
     */
    public function handleS3(S3Event $event, Context $context): void
    {
        $this->parseMail($event);

//        $result = $this->getFileObject($event);
//
//        $this->s3Client->putObject([
//            'Bucket' => self::BUCKET_NAME,
//            'Key'    => self::BUCKET_KEY_PREFIX . basename($event->getRecords()[0]->getObject()->getKey()),
//            'Body'   => $result->get('Body'),
//        ]);
    }

    /**
     * @param S3Event $event
     *
     * @return Result
     *
     * @throws InvalidLambdaEvent
     */
    private function getFileObject(S3Event $event): Result
    {
        $record = $event->getRecords()[0];

        return $this->s3Client->getObject([
            'Bucket' => self::BUCKET_NAME,
            'Key'    => $record->getObject()->getKey(),
        ]);
    }

    /**
     * @param S3Event $event
     *
     * @return void
     *
     * @throws InvalidLambdaEvent
     */
    private function parseMail(S3Event $event): void
    {
        $mail    = $event->getRecords()[0]->getObject();
        $message = $this->mailParser->parse($mail, false);

        for ($i = 0; $i < $message->getAttachmentCount(); $i++) {
            $part = $message->getAttachmentPart($i);

            if ($part === null) {
                continue;
            }

            $this->logger->info($part->getContent());
        }
    }
}

return new Handler(
    new StderrLogger(LogLevel::INFO),
    new MailMimeParser(),
    new S3Client(['region' => 'ap-northeast-1'])
);

<?php
/**
 * Created by PhpStorm.
 * User: p2
 * Date: 8/18/14
 * Time: 1:41 PM
 */
namespace Main\Helper;

class APNHelper {
    const PAYLOAD_MAX_BYTES = 256;

    protected $host, $post, $errorString, $error, $apnsResource;
    public function __construct($certString, $host, $port){
        $this->certString = $certString;
        $this->host = $host;
        $this->port = $port;
    }

    protected function getApnsResource()
    {
        if (!is_resource($this->apnsResource)) {
            $this->apnsResource = $this->createStreamClient();
        }

        return $this->apnsResource;
    }

    protected function createStreamContext()
    {
        $streamContext = stream_context_create();
        stream_context_set_option($streamContext, 'ssl', 'local_cert', $this->writeToTmp($this->certString));

        return $streamContext;
    }

    protected function createStreamClient()
    {
        $address = $this->getSocketAddress();

        $client = @stream_socket_client(
            $address,
            $this->error,
            $this->errorString,
            2,
            STREAM_CLIENT_CONNECT,
            $this->createStreamContext()
        );

        if (!$client) {
            throw new \Exception(
                sprintf('Failed to create stream socket client to "%s". %s', $address, $this->errorString), $this->error
            );
        }

        return $client;
    }

    protected function getSocketAddress()
    {
        return sprintf('ssl://%s:%s', $this->host, $this->port);
    }

    protected function writeToTmp($certString)
    {
        $path = tempnam(sys_get_temp_dir(), 'cert_');
        file_put_contents($path, $certString);

        return $path;
    }

    protected function makePayload($message, $args){
        return array(
            'aps'=> array(
                'alert'=> $message
            ),
            'args' => $args
        );
    }

    protected function getBinaryMessage($deviceToken, $message, $args)
    {
        $encodedPayload = json_encode($this->makePayload($message, $args));

        return chr(0).
            chr(0).
            chr(32).
            pack('H*', $deviceToken).
            chr(0).chr(strlen($encodedPayload)).
            $encodedPayload;
    }

    public function write($binaryMessage){
        $size = strlen($binaryMessage);
        if ($size > self::PAYLOAD_MAX_BYTES) {
            throw new \Exception(
                sprintf('APN can not send %s bytes max size is %s bytes;', $size, self::PAYLOAD_MAX_BYTES)
            );
        }

        return fwrite($this->getApnsResource(), $binaryMessage);
    }

    public function send($deviceToken, $message, $args){
        $binaryMessage = $this->getBinaryMessage($deviceToken, $message, $args);
        return $this->write($binaryMessage);
    }
}
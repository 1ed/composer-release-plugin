<?php

namespace egabor\Composer\ReleasePlugin\Test;

use PHPUnit\Framework\TestCase;

abstract class ForwardCompatibleTestCase extends TestCase
{
    private $expectedException;
    private $expectedExceptionMessage = '';
    private $expectedExceptionCode;

    public function expectException($exception)
    {
        if (is_callable('parent::expectException')) {
            parent::expectException($exception);
        } else {
            $this->expectedException = $exception;
            $this->setExpectedException($exception, $this->expectedExceptionMessage, $this->expectedExceptionCode);
        }
    }

    public function expectExceptionMessage($message)
    {
        if (is_callable('parent::expectExceptionMessage')) {
            parent::expectExceptionMessage($message);
        } else {
            $this->expectedExceptionMessage = $message;
            $this->setExpectedException($this->expectedException, $message, $this->expectedExceptionCode);
        }
    }

    public function expectExceptionCode($code)
    {
        if (is_callable('parent::expectExceptionCode')) {
            parent::expectExceptionCode($code);
        } else {
            $this->expectedExceptionCode = $code;
            $this->setExpectedException($this->expectedException, $this->expectedExceptionMessage, $code);
        }
    }

    public function expectExceptionObject(\Exception $exception)
    {
        if (is_callable('parent::expectExceptionObject')) {
            parent::expectExceptionObject($exception);
        } else {
            $this->expectException(\get_class($exception));
            $this->expectExceptionMessage($exception->getMessage());
            $this->expectExceptionCode($exception->getCode());
        }
    }

    protected function createMock($originalClassName)
    {
        if (is_callable('parent::createMock')) {
            return parent::createMock($originalClassName);
        }

        return $this->getMockBuilder($originalClassName)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->getMock();
    }
}

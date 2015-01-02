<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

namespace ProxyManagerTest\Functional;

use PHPUnit_Framework_TestCase;
use ProxyManager\Generator\ClassGenerator;
use ProxyManager\Generator\Util\UniqueIdentifierGenerator;
use ProxyManager\GeneratorStrategy\EvaluatingGeneratorStrategy;
use ProxyManager\ProxyGenerator\NullObjectGenerator;
use ProxyManagerTestAsset\BaseClass;
use ProxyManagerTestAsset\BaseInterface;
use ProxyManagerTestAsset\ClassWithSelfHint;
use ReflectionClass;

/**
 * Tests for {@see \ProxyManager\ProxyGenerator\NullObjectGenerator} produced objects
 *
 * @author Vincent Blanchon <blanchon.vincent@gmail.com>
 * @license MIT
 *
 * @group Functional
 * @coversNothing
 */
class NullObjectFunctionalTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getProxyMethods
     *
     * @param string  $className
     * @param string  $method
     * @param mixed[] $params
     */
    public function testMethodCalls($className, $method, $params)
    {
        $proxyName = $this->generateProxy($className);

        /* @var $proxy \ProxyManager\Proxy\NullObjectInterface */
        $proxy = $proxyName::staticProxyConstructor();

        $this->assertSame(null, call_user_func_array([$proxy, $method], $params));
    }

    /**
     * @dataProvider getProxyMethods
     *
     * @param string  $className
     * @param string  $method
     * @param mixed[] $params
     */
    public function testMethodCallsAfterUnSerialization($className, $method, $params)
    {
        $proxyName = $this->generateProxy($className);
        /* @var $proxy \ProxyManager\Proxy\NullObjectInterface */
        $proxy     = unserialize(serialize($proxyName::staticProxyConstructor()));

        $this->assertSame(null, call_user_func_array([$proxy, $method], $params));
    }

    /**
     * @dataProvider getProxyMethods
     *
     * @param string  $className
     * @param string  $method
     * @param mixed[] $params
     */
    public function testMethodCallsAfterCloning($className, $method, $params)
    {
        $proxyName = $this->generateProxy($className);

        /* @var $proxy \ProxyManager\Proxy\NullObjectInterface */
        $proxy     = $proxyName::staticProxyConstructor();
        $cloned    = clone $proxy;

        $this->assertSame(null, call_user_func_array([$cloned, $method], $params));
    }

    /**
     * @dataProvider getPropertyAccessProxies
     *
     * @param \ProxyManager\Proxy\NullObjectInterface $proxy
     * @param string                                  $publicProperty
     */
    public function testPropertyReadAccess($proxy, $publicProperty)
    {
        $this->assertSame(null, $proxy->$publicProperty);
    }

    /**
     * @dataProvider getPropertyAccessProxies
     *
     * @param \ProxyManager\Proxy\NullObjectInterface $proxy
     * @param string                                  $publicProperty
     */
    public function testPropertyWriteAccess($proxy, $publicProperty)
    {
        $newValue               = uniqid();
        $proxy->$publicProperty = $newValue;

        $this->assertSame($newValue, $proxy->$publicProperty);
    }

    /**
     * @dataProvider getPropertyAccessProxies
     *
     * @param \ProxyManager\Proxy\NullObjectInterface $proxy
     * @param string                                  $publicProperty
     */
    public function testPropertyExistence($proxy, $publicProperty)
    {
        $this->assertSame(null, $proxy->$publicProperty);
    }

    /**
     * @dataProvider getPropertyAccessProxies
     *
     * @param \ProxyManager\Proxy\NullObjectInterface $proxy
     * @param string                                  $publicProperty
     */
    public function testPropertyUnset($proxy, $publicProperty)
    {
        unset($proxy->$publicProperty);

        $this->assertFalse(isset($proxy->$publicProperty));
    }

    /**
     * Generates a proxy for the given class name, and retrieves its class name
     *
     * @param string $parentClassName
     *
     * @return string
     */
    private function generateProxy($parentClassName)
    {
        $generatedClassName = __NAMESPACE__ . '\\' . UniqueIdentifierGenerator::getIdentifier('Foo');
        $generator          = new NullObjectGenerator();
        $generatedClass     = new ClassGenerator($generatedClassName);
        $strategy           = new EvaluatingGeneratorStrategy();

        $generator->generate(new ReflectionClass($parentClassName), $generatedClass);
        $strategy->generate($generatedClass);

        return $generatedClassName;
    }

    /**
     * Generates a list of object | invoked method | parameters | expected result
     *
     * @return array
     */
    public function getProxyMethods()
    {
        $selfHintParam = new ClassWithSelfHint();

        return [
            [
                BaseClass::class,
                'publicMethod',
                [],
                'publicMethodDefault'
            ],
            [
                BaseClass::class,
                'publicTypeHintedMethod',
                ['param' => new \stdClass()],
                'publicTypeHintedMethodDefault'
            ],
            [
                BaseClass::class,
                'publicByReferenceMethod',
                [],
                'publicByReferenceMethodDefault'
            ],
            [
                BaseInterface::class,
                'publicMethod',
                [],
                'publicMethodDefault'
            ],
            [
                ClassWithSelfHint::class,
                'selfHintMethod',
                ['parameter' => $selfHintParam],
                $selfHintParam
            ],
        ];
    }

    /**
     * Generates proxies and instances with a public property to feed to the property accessor methods
     *
     * @return array
     */
    public function getPropertyAccessProxies()
    {
        $proxyName1 = $this->generateProxy(BaseClass::class);
        $proxyName2 = $this->generateProxy(BaseClass::class);

        return [
            [
                $proxyName1::staticProxyConstructor(),
                'publicProperty',
                'publicPropertyDefault',
            ],
            [
                unserialize(serialize($proxyName2::staticProxyConstructor())),
                'publicProperty',
                'publicPropertyDefault',
            ],
        ];
    }
}

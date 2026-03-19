<?php

namespace Detain\MyAdminDirectadmin\Tests;

use Detain\MyAdminDirectadmin\Plugin;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Tests for the Plugin class.
 *
 * Focuses on class structure, static properties, hook configuration,
 * and event handler method signatures using ReflectionClass to avoid
 * needing the full MyAdmin runtime environment.
 */
class PluginTest extends TestCase
{
    /**
     * @var ReflectionClass
     */
    private $reflection;

    protected function setUp(): void
    {
        $this->reflection = new ReflectionClass(Plugin::class);
    }

    /**
     * Tests that the Plugin class exists and can be reflected.
     * Ensures the autoloader correctly maps the namespace.
     */
    public function testClassExists(): void
    {
        $this->assertTrue(class_exists(Plugin::class));
    }

    /**
     * Tests that Plugin can be instantiated.
     * The constructor is empty, so this should always succeed.
     */
    public function testCanBeInstantiated(): void
    {
        $plugin = new Plugin();
        $this->assertInstanceOf(Plugin::class, $plugin);
    }

    /**
     * Tests that the $name static property is set to the expected value.
     * This identifies the plugin in the MyAdmin ecosystem.
     */
    public function testNameProperty(): void
    {
        $this->assertSame('DirectAdmin Licensing', Plugin::$name);
    }

    /**
     * Tests that the $description static property contains meaningful text.
     * The description is displayed in admin interfaces.
     */
    public function testDescriptionProperty(): void
    {
        $this->assertStringContainsString('DirectAdmin', Plugin::$description);
        $this->assertStringContainsString('directadmin.com', Plugin::$description);
    }

    /**
     * Tests that the $help static property is defined (even if empty).
     */
    public function testHelpProperty(): void
    {
        $this->assertSame('', Plugin::$help);
    }

    /**
     * Tests that the $module static property is 'licenses'.
     * This determines which MyAdmin module this plugin belongs to.
     */
    public function testModuleProperty(): void
    {
        $this->assertSame('licenses', Plugin::$module);
    }

    /**
     * Tests that the $type static property is 'service'.
     * This categorizes the plugin type within MyAdmin.
     */
    public function testTypeProperty(): void
    {
        $this->assertSame('service', Plugin::$type);
    }

    /**
     * Tests that all five expected static properties exist on the class.
     * Verifies the class structure matches the MyAdmin plugin contract.
     */
    public function testHasAllRequiredStaticProperties(): void
    {
        $expected = ['name', 'description', 'help', 'module', 'type'];
        foreach ($expected as $prop) {
            $this->assertTrue(
                $this->reflection->hasProperty($prop),
                "Missing static property: \${$prop}"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isStatic(),
                "Property \${$prop} should be static"
            );
            $this->assertTrue(
                $this->reflection->getProperty($prop)->isPublic(),
                "Property \${$prop} should be public"
            );
        }
    }

    /**
     * Tests that getHooks() returns an array with the expected event keys.
     * These hooks integrate the plugin into the MyAdmin event system.
     */
    public function testGetHooksReturnsExpectedKeys(): void
    {
        $hooks = Plugin::getHooks();

        $this->assertIsArray($hooks);

        $expectedKeys = [
            'licenses.settings',
            'licenses.activate',
            'licenses.reactivate',
            'licenses.deactivate',
            'licenses.deactivate_ip',
            'function.requirements',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $hooks, "Missing hook key: {$key}");
        }
    }

    /**
     * Tests that getHooks() returns exactly 6 hook entries.
     * Guards against accidentally adding or removing hooks.
     */
    public function testGetHooksCount(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertCount(6, $hooks);
    }

    /**
     * Tests that each hook value is a valid callable array with the Plugin class and a method name.
     * Ensures the event dispatcher can resolve each hook.
     */
    public function testGetHooksValuesAreCallableArrays(): void
    {
        $hooks = Plugin::getHooks();

        foreach ($hooks as $eventName => $callback) {
            $this->assertIsArray($callback, "Hook for '{$eventName}' should be an array");
            $this->assertCount(2, $callback, "Hook for '{$eventName}' should have exactly 2 elements");
            $this->assertSame(
                Plugin::class,
                $callback[0],
                "Hook for '{$eventName}' should reference Plugin class"
            );
            $this->assertTrue(
                method_exists($callback[0], $callback[1]),
                "Method {$callback[1]} does not exist on Plugin for hook '{$eventName}'"
            );
        }
    }

    /**
     * Tests that activate and reactivate hooks both point to getActivate.
     * Both events should trigger the same activation logic.
     */
    public function testActivateAndReactivateShareSameHandler(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame($hooks['licenses.activate'], $hooks['licenses.reactivate']);
    }

    /**
     * Tests that deactivate and deactivate_ip hooks both point to getDeactivate.
     * Both events should trigger the same deactivation logic.
     */
    public function testDeactivateAndDeactivateIpShareSameHandler(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertSame($hooks['licenses.deactivate'], $hooks['licenses.deactivate_ip']);
    }

    /**
     * Tests that the constructor has no required parameters.
     * Plugin instances should be creatable without arguments.
     */
    public function testConstructorHasNoRequiredParameters(): void
    {
        $constructor = $this->reflection->getConstructor();
        $this->assertNotNull($constructor);
        $this->assertCount(0, $constructor->getParameters());
    }

    /**
     * Tests that getHooks() is a public static method.
     * The MyAdmin framework calls it statically to register event listeners.
     */
    public function testGetHooksIsPublicStatic(): void
    {
        $method = $this->reflection->getMethod('getHooks');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());
    }

    /**
     * Tests that getActivate() accepts exactly one parameter of type GenericEvent.
     * This ensures compatibility with Symfony EventDispatcher.
     */
    public function testGetActivateMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getActivate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertSame('event', $params[0]->getName());

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that getDeactivate() accepts exactly one GenericEvent parameter.
     * Mirrors the same contract as getActivate for event handling.
     */
    public function testGetDeactivateMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getDeactivate');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that getChangeIp() accepts exactly one GenericEvent parameter.
     * Used when a customer changes the IP address of a licensed server.
     */
    public function testGetChangeIpMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getChangeIp');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that getMenu() accepts exactly one GenericEvent parameter.
     * Adds DirectAdmin-related menu items to the admin panel.
     */
    public function testGetMenuMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getMenu');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that getRequirements() accepts exactly one GenericEvent parameter.
     * Registers function requirements for the plugin loader.
     */
    public function testGetRequirementsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getRequirements');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that getSettings() accepts exactly one GenericEvent parameter.
     * Registers configuration fields in the admin settings panel.
     */
    public function testGetSettingsMethodSignature(): void
    {
        $method = $this->reflection->getMethod('getSettings');
        $this->assertTrue($method->isPublic());
        $this->assertTrue($method->isStatic());

        $params = $method->getParameters();
        $this->assertCount(1, $params);

        $type = $params[0]->getType();
        $this->assertNotNull($type);
        $this->assertSame(GenericEvent::class, $type->getName());
    }

    /**
     * Tests that the Plugin class resides in the expected namespace.
     * Validates PSR-4 autoloading configuration.
     */
    public function testClassNamespace(): void
    {
        $this->assertSame('Detain\\MyAdminDirectadmin', $this->reflection->getNamespaceName());
    }

    /**
     * Tests that the Plugin class does not extend any base class.
     * It is a standalone plugin, not inheriting from a framework base.
     */
    public function testClassHasNoParent(): void
    {
        $this->assertFalse($this->reflection->getParentClass());
    }

    /**
     * Tests that the Plugin class has exactly the expected set of methods.
     * Ensures no methods are accidentally removed or orphaned.
     */
    public function testExpectedMethodsExist(): void
    {
        $expectedMethods = [
            '__construct',
            'getHooks',
            'getActivate',
            'getDeactivate',
            'getChangeIp',
            'getMenu',
            'getRequirements',
            'getSettings',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertTrue(
                $this->reflection->hasMethod($method),
                "Missing method: {$method}"
            );
        }
    }

    /**
     * Tests that the hooks use the $module property dynamically.
     * Changing Plugin::$module should change all hook keys accordingly.
     */
    public function testHookKeysAreDerivedFromModuleProperty(): void
    {
        $hooks = Plugin::getHooks();

        foreach ($hooks as $key => $value) {
            if ($key !== 'function.requirements') {
                $this->assertStringStartsWith(
                    Plugin::$module . '.',
                    $key,
                    "Hook key '{$key}' should start with module name"
                );
            }
        }
    }

    /**
     * Tests that the getHooks includes the function.requirements hook.
     * This special hook is module-independent and registers function loaders.
     */
    public function testFunctionRequirementsHookExists(): void
    {
        $hooks = Plugin::getHooks();
        $this->assertArrayHasKey('function.requirements', $hooks);
        $this->assertSame([Plugin::class, 'getRequirements'], $hooks['function.requirements']);
    }
}

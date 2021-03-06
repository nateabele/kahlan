<?php
namespace spec\plugin;

use kahlan\Arg;
use kahlan\jit\Interceptor;
use kahlan\jit\Patchers;
use kahlan\jit\patcher\Pointcut;
use kahlan\analysis\Parser;
use kahlan\plugin\Stub;

use spec\fixture\plugin\pointcut\Foo;

describe("Stub::on", function() {

	/**
	 * Save current & reinitialize the Interceptor class.
	 */
	before(function() {
		$this->previous = Interceptor::loader();
		Interceptor::unpatch();

		$patchers = new Patchers();
		$patchers->add('pointcut', new Pointcut());
		Interceptor::patch(compact('patchers'));
	});

	/**
	 * Restore Interceptor class.
	 */
	after(function() {
		Interceptor::loader($this->previous);
	});

	context("with an instance", function() {

		it("stubs a method", function() {
			$foo = new Foo();
			Stub::on($foo)->method('message')->andReturn('Good Bye!');
			expect($foo->message())->toBe('Good Bye!');
		});

		it("stubs only on the stubbed instance", function() {
			$foo = new Foo();
			Stub::on($foo)->method('message')->andReturn('Good Bye!');
			expect($foo->message())->toBe('Good Bye!');

			$foo2 = new Foo();
			expect($foo2->message())->toBe('Hello World!');
		});

		it("stubs a method using a closure", function() {
			$foo = new Foo();
			Stub::on($foo)->method('message', function($param) { return $param; });
			expect($foo->message('Good Bye!'))->toBe('Good Bye!');
		});

		it("stubs a magic method", function() {
			$foo = new Foo();
			Stub::on($foo)->method('magicCall')->andReturn('Magic Call!');
			expect($foo->magicCall())->toBe('Magic Call!');
		});

		it("stubs a magic method using a closure", function() {
			$foo = new Foo();
			Stub::on($foo)->method('magicHello', function($message) { return $message; });
			expect($foo->magicHello('Hello World!'))->toBe('Hello World!');
		});

		it("stubs a static magic method", function() {
			$foo = new Foo();
			Stub::on($foo)->method('::magicCallStatic')->andReturn('Magic Call Static!');
			expect($foo::magicCallStatic())->toBe('Magic Call Static!');
		});

		it("stubs a static magic method using a closure", function() {
			$foo = new Foo();
			Stub::on($foo)->method('::magicHello', function($message) { return $message; });
			expect($foo::magicHello('Hello World!'))->toBe('Hello World!');
		});

		context("using the with() parameter", function() {

			it("stubs on matched parameter", function() {
				$foo = new Foo();
				Stub::on($foo)->method('message')->with('Hello World!')->andReturn('Good Bye!');
				expect($foo->message('Hello World!'))->toBe('Good Bye!');
			});

			it("doesn't stubs on unmatched parameter", function() {
				$foo = new Foo();
				Stub::on($foo)->method('message')->with('Hello World!')->andReturn('Good Bye!');
				expect($foo->message('Hello!'))->not->toBe('Good Bye!');
			});

		});

		context("using the with() parameter and the argument matchers", function() {

			it("stubs on matched parameter", function() {
				$foo = new Foo();
				Stub::on($foo)->method('message')->with(Arg::toBeA('string'))->andReturn('Good Bye!');
				expect($foo->message('Hello World!'))->toBe('Good Bye!');
				expect($foo->message('Hello'))->toBe('Good Bye!');
			});

			it("doesn't stubs on unmatched parameter", function() {
				$foo = new Foo();
				Stub::on($foo)->method('message')->with(Arg::toBeA('string'))->andReturn('Good Bye!');
				expect($foo->message(false))->not->toBe('Good Bye!');
				expect($foo->message(['Hello World!']))->not->toBe('Good Bye!');
			});

		});

		context("with multiple return values", function(){

			it("stubs a method", function() {
				$foo = new Foo();
				Stub::on($foo)->method('message')->andReturn('Good Evening World!', 'Good Bye World!');
				expect($foo->message())->toBe('Good Evening World!');
				expect($foo->message())->toBe('Good Bye World!');
				expect($foo->message())->toBe('Good Bye World!');
			});

			it("stubs methods with an array", function() {
				$foo = new Foo();
				Stub::on($foo)->method([
					'message' => ['Good Evening World!', 'Good Bye World!'],
					'bar' => ['Hello Bar!']
				]);
				expect($foo->message())->toBe('Good Evening World!');
				expect($foo->message())->toBe('Good Bye World!');
				expect($foo->bar())->toBe('Hello Bar!');
			});

		});

	});

	context("with an class", function() {

		it("stubs a method", function() {
			Stub::on('spec\fixture\plugin\pointcut\Foo')
				->method('message')
				->andReturn('Good Bye!');

			$foo = new Foo();
			expect($foo->message())->toBe('Good Bye!');
			$foo2 = new Foo();
			expect($foo2->message())->toBe('Good Bye!');
		});

		it("stubs a static method", function() {
			Stub::on('spec\fixture\plugin\pointcut\Foo')->method('::messageStatic')->andReturn('Good Bye!');
			expect(Foo::messageStatic())->toBe('Good Bye!');
		});

		it("stubs a method using a closure", function() {
			Stub::on('spec\fixture\plugin\pointcut\Foo')->method('message', function($param) { return $param; });
			$foo = new Foo();
			expect($foo->message('Good Bye!'))->toBe('Good Bye!');
		});

		it("stubs a static method using a closure", function() {
			Stub::on('spec\fixture\plugin\pointcut\Foo')->method('::messageStatic', function($param) { return $param; });
			expect(Foo::messageStatic('Good Bye!'))->toBe('Good Bye!');
		});

		context("with multiple return values", function(){

			it("stubs a method", function() {
				Stub::on('spec\fixture\plugin\pointcut\Foo')
					->method('message')
					->andReturn('Good Evening World!', 'Good Bye World!');

				$foo = new Foo();
				expect($foo->message())->toBe('Good Evening World!');

				$foo2 = new Foo();
				expect($foo2->message())->toBe('Good Bye World!');
			});

			it("stubs methods with an array", function() {
				Stub::on('spec\fixture\plugin\pointcut\Foo')->method([
					'message' => ['Good Evening World!', 'Good Bye World!'],
					'bar' => ['Hello Bar!']
				]);

				$foo = new Foo();
				expect($foo->message())->toBe('Good Evening World!');

				$foo2 = new Foo();
				expect($foo2->message())->toBe('Good Bye World!');

				$foo3 = new Foo();
				expect($foo3->bar())->toBe('Hello Bar!');
			});
		});

	});

});

describe("Stub::create", function() {

	/**
	 * Save current & reinitialize the Interceptor class.
	 */
	before(function() {
		$this->previous = Interceptor::loader();
		Interceptor::unpatch();

		$patchers = new Patchers();
		$patchers->add('pointcut', new Pointcut());
		Interceptor::patch(compact('patchers'));
	});

	/**
	 * Restore Interceptor class.
	 */
	after(function() {
		Interceptor::loader($this->previous);
	});

	it("stubs an instance", function() {
		$stub = Stub::create();
		expect(is_object($stub))->toBe(true);
		expect(get_class($stub))->toMatch("/^spec\\\plugin\\\stub\\\Stub\d+$/");
	});

	it("names a stub instance", function() {
		$stub = Stub::create(['class' => 'spec\stub\MyStub']);
		expect(is_object($stub))->toBe(true);
		expect(get_class($stub))->toBe('spec\stub\MyStub');
	});

	it("stubs an instance extending a parent class", function() {
		$stub = Stub::create(['extends' => 'kahlan\util\String']);
		expect(is_object($stub))->toBe(true);
		expect(get_parent_class($stub))->toBe('kahlan\util\String');
	});

	it("stubs an instance using a trait", function() {
		$stub = Stub::create(['uses' => 'spec\mock\plugin\stub\HelloTrait']);
		expect($stub->hello())->toBe('Hello World From Trait!');
	});

	it("stubs an instance implementing some interface", function() {
		$stub = Stub::create(['implements' => ['ArrayAccess', 'Iterator']]);
		$interfaces = class_implements($stub);
		expect($interfaces)->toHaveLength(3);
		expect(isset($interfaces['ArrayAccess']))->toBe(true);
		expect(isset($interfaces['Iterator']))->toBe(true);
		expect(isset($interfaces['Traversable']))->toBe(true);
	});

	it("stubs a stub instance with multiple methods", function() {
		$stub = Stub::create();
		Stub::on($stub)->method([
			'message' => ['Good Evening World!', 'Good Bye World!'],
			'bar' => ['Hello Bar!']
		]);

		expect($stub->message())->toBe('Good Evening World!');
		expect($stub->message())->toBe('Good Bye World!');
		expect($stub->bar())->toBe('Hello Bar!');
	});

	it("stubs static methods on a stub instance", function() {
		$stub = Stub::create();
		Stub::on($stub)->method([
			'::magicCallStatic' => ['Good Evening World!', 'Good Bye World!']
		]);

		expect($stub::magicCallStatic())->toBe('Good Evening World!');
		expect($stub::magicCallStatic())->toBe('Good Bye World!');
	});

	it("produces unique instance", function() {
		$stub = Stub::create();
		$stub2 = Stub::create();

		expect(get_class($stub))->not->toBe(get_class($stub2));
	});

	it("stubs class", function() {
		$stub = Stub::classname();
		expect($stub)->toMatch("/^spec\\\plugin\\\stub\\\Stub\d+$/");
	});

	it("names a stub class", function() {
		$stub = Stub::classname(['class' => 'spec\stub\MyStaticStub']);
		expect(is_string($stub))->toBe(true);
		expect($stub)->toBe('spec\stub\MyStaticStub');
	});

	it("stubs a stub class with multiple methods", function() {
		$classname = Stub::classname();
		Stub::on($classname)->method([
			'message' => ['Good Evening World!', 'Good Bye World!'],
			'bar' => ['Hello Bar!']
		]);

		$stub = new $classname();
		expect($stub->message())->toBe('Good Evening World!');

		$stub2 = new $classname();
		expect($stub->message())->toBe('Good Bye World!');

		$stub3 = new $classname();
		expect($stub->bar())->toBe('Hello Bar!');
	});

	it("stubs static methods on a stub class", function() {
		$classname = Stub::classname();
		Stub::on($classname)->method([
			'::magicCallStatic' => ['Good Evening World!', 'Good Bye World!']
		]);

		expect($classname::magicCallStatic())->toBe('Good Evening World!');
		expect($classname::magicCallStatic())->toBe('Good Bye World!');
	});

	it("produces unique classname", function() {
		$stub = Stub::classname();
		$stub2 = Stub::classname();

		expect($stub)->not->toBe($stub2);
	});

});
?>
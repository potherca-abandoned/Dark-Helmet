<?php

/**
 * All tests should extend this, so we can add generic functionality if required.
 */
abstract class TestCase extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @test
	 */
	public function propertiesMustNotBePublic()
	{
		$className = substr(get_called_class(), 0, -4);
		
		try {
			$reflectionClass = new ReflectionClass($className);
			$properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
		} catch (\Exception $ex) {
			// This is designed to fail hard.
			$this->assertTrue(false, sprintf('Unable to find class "%s"', $className));
			$properties = array();
		}
		
		$this->assertSame(0, count($properties));
	}
	
}
<?php

/**
 * 
 */
class TestCase extends \PHPUnit_Framework_TestCase {
	
	/**
	 * @test
	 */
	public function propertiesMustNotBePublic()
	{
		$className = substr(get_called_class(), 0, -4);
		
		// This works for both abstract and concrete classes
		$mock = $this->getMockForAbstractClass($className);
		$reflectionClass = new ReflectionClass($mock);
		
		$properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
		$this->assertSame(0, count($properties));
	}
	
}
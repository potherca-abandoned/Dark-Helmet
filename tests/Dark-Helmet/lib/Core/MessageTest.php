<?php

namespace DarkHelmet\Core
{
	class MessageTest extends \TestCase
	{
		protected function setUp() {
			
		}
		
		protected function tearDown() {
			
		}
		
		/**
		 * @test
		 * @covers \DarkHelmet\Core\Message::__construct
		 * @covers \DarkHelmet\Core\Message::getText
		 */
		public function __construct_ReturnsMessageWithText_WhenCalledWithText() {
			$sText = 'Test message';
			$oMessage = new Message($sText);
			$this->assertInstanceOf('\\DarkHelmet\\Core\\Message', $oMessage);
			$this->assertSame($sText, $oMessage->getText());
		}
		
		/**
		 * @test
		 * @covers \DarkHelmet\Core\Message::__construct
		 * @covers \DarkHelmet\Core\Message::getText
		 */
		public function __construct_ReturnsMessageWithTextAndSeverity_WhenCalledWithBoth() {
			$sText = 'Other test message';
			$iSeverity = Message::SEVERITY_NOTICE;
			$oMessage = new Message($sText, $iSeverity);
			$this->assertInstanceOf('\\DarkHelmet\\Core\\Message', $oMessage);
			$this->assertSame($sText, $oMessage->getText());
			$this->assertSame($iSeverity, $oMessage->getSeverity());
		}
	}
}
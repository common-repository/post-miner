<?php

class EngineTest extends PHPUnit_Framework_TestCase
{
    private $engine;
    
    static function setUpBeforeClass()
    {
        require( realpath(dirname(__FILE__)) . '/../post-miner-test.php' );
        require_once( POST_MINER__PATH . 'Engine.php' );
    }
    
    protected function setUp()
    {
        $this->engine = new PostMiner_Engine();
    }
    
    public function testStringToVectorConvertion()
    {
        $vector = $this->engine->createTermVector( "Artificial Intelligence in             WEB2.0", 1.2 );
        
        $this->assertEquals( 3, count( $vector->getValues() ) );
                
        $this->assertEquals( 0, bccomp( $vector->getValue('artifici'), '0.83333', 5 ) );
        $this->assertEquals( 0, bccomp( $vector->getValue('intellig'), '0.69444', 5 ) );
        $this->assertEquals( 0, bccomp( $vector->getValue('web20'), '0.57870', 5 ) );
    }

}
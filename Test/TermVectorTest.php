<?php

class TermVectorTest extends PHPUnit_Framework_TestCase
{
    
    static function setUpBeforeClass()
    {
        require( realpath(dirname(__FILE__)) . '/../post-miner-test.php' );
        require_once( POST_MINER__PATH . 'TermVector.php' );
    }
    

    public function testSum()
    {   
        $vector1 = new PostMiner_TermVector( array( 'wordpress' => 0.5, 'blogging' => 0.5 ) );
        $vector2 = new PostMiner_TermVector( array( 'mac' => 1, 'blogging' => 0.5 ) );
        $vector = $vector1->sum( $vector2 );
        $vector->normalize();
        
        $this->assertEquals( 3, count( $vector->getValues() ) );
                
        $this->assertEquals( 0, bccomp( $vector->getValue('wordpress'), '0.33333', 5 ) );
        $this->assertEquals( 0, bccomp( $vector->getValue('blogging'), '0.66666', 5 ) );
        $this->assertEquals( 0, bccomp( $vector->getValue('mac'), '0.66666', 5 ) );
    }

}
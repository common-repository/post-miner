<?php

/**
 * Term vector class. 
 * 
 * @author Lukasz Kujawa <lukasz.f24@gmail.com>
 * @category PostMiner
 */
class PostMiner_TermVector
{
    /**
     * Vector values (dimentions)
     * @var array of float
     */
    private $values;
    
    /**
     * Constructor
     * @param array $values an array with dimention names and values
     */
    public function __construct( Array $values ) 
    {
        $this->values = $values;
    }
    
    /**
     * Returns values array
     * 
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }
    
    /**
     * Returns vector value for a particula dimension
     * 
     * @param string $name dimension name
     * @return float
     */
    public function getValue( $name )
    {
        if( isset( $this->values[ $name ] ) )
        {
            return  $this->values[ $name ];
        }
        
        return 0;
    }
    
    /**
     * Add vectors
     * 
     * @param PostMiner_TermVector $vector vector to be added
     * @return PostMiner_TermVector result
     */
    public function sum( PostMiner_TermVector $vector )
    {
        $v1 = $this->getValues();
        $v2 = $vector->getValues();
        
        $allDimensions = array_merge( array_keys( $v1 ), array_keys( $v2 ) );
        $allDimensions = array_unique( $allDimensions );
        
        $newVector = array();
        
        foreach( $allDimensions as $name )
        {
            $val1 = isset( $v1[ $name ] ) ? $v1[ $name ] : 0;
            $val2 = isset( $v2[ $name ] ) ? $v2[ $name ] : 0;
            
            $this->values[ $name ] = $val1 + $val2;
        }
        
        return $this;
    }
    
    /**
     * Multiply vector by a number
     * 
     * @param float $number
     * @return PostMiner_TermVector 
     */
    public function mul( $number )
    {
        foreach( $this->values as $name => $value )
        {
            $this->values[ $name ] *= $value;
        }
        
        return $this;
    }
    
    /**
     * Returns number of dimensions
     * 
     * @return integer
     */
    public function getDimSize()
    {
        return sizeof( $this->values );
    }
    
    /**
     * Returns all dimension names
     *
     * @return array
     */
    public function getDimensions()
    {
        return array_keys( $this->values );
    }
    
    /**
     * Normalizes vector
     * 
     */
    public function normalize()
    {
        $total = 0;
        
        foreach( $this->values as $value )
        {
            $total += $value * $value;
        }
        
        $total = sqrt( $total );
        
        foreach( $this->values as $dim => $value )
        {
            $this->values[ $dim ] /= $total;
        }
    }
    
    /**
     * Converts a vector to string
     *
     * @return String
     */
    public function __toString() 
    {
       
        $values = array();
        
        foreach( $this->values as $name => $val )
        {
            $values[] = $name . '=' .$val;
        }
        
        return sprintf("PostMiner_TermVector\n[\n%s\n]\n", implode(";\n", $values ) );
    }
}
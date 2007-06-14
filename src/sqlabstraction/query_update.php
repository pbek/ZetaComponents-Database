<?php
/**
 * File containing the ezcQueryUpdate class.
 *
 * @package Database
 * @version //autogentag//
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 */

/**
 * Class to create select database independent UPDATE queries.
 *
 * ezcQueryUpdate does not support updating from via a select call.
 *
 * Note that this class creates queries that are syntactically independant
 * of database. Semantically the queries still differ and so the same
 * query may produce different results on different databases. Such
 * differences are noted throughout the documentation of this class.
 *
 * This class implements SQL92. If your database differs from the SQL92
 * implementation extend this class and reimplement the methods that produce
 * different results. Some methods implemented in ezcQuery are not defined by SQL92.
 * These methods are marked and ezcQuery will return MySQL syntax for these cases.
 *
 * The examples show the SQL generated by this class.
 * Database specific implementations may produce different results.
 *
 * Example:
 * <code>
 * $q = ezcDbInstance::get()->createUpdateQuery();
 * $q->update( 'legends' )
 *     ->set( 'Gretzky', 99 )
 *     ->set( 'Lindros', 88 );
 * $stmt = $q->prepare();
 * $stmt->execute();
 * </code>
 *
 * @package Database
 * @mainclass
 * @version //autogentag//
 */
class ezcQueryUpdate extends ezcQuery
{
    /**
     * Holds the columns and the values that should inserted into the the table.
     *
     * Format array('column'=>value)
     * @var array(string=>mixed)
     */
    private $values = array();

    /**
     * The target table for the update query.
     *
     * @var string
     */
    private $table = null;

    /**
     * Stores the WHERE part of the SQL.
     *
     * @var string
     */
    protected $whereString = null;


    /**
     * Constructs a new ezcQueryUpdate that works on the database $db and with the aliases $aliases.
     *
     * The parameters are passed directly to ezcQuery.
     * @param PDO $db
     * @param array(string=>string) $aliases
     */
    public function __construct( PDO $db, array $aliases = array() )
    {
        parent::__construct( $db, $aliases );
    }

    /**
     * Opens the query and sets the target table to $table.
     *
     * update() returns a pointer to $this.
     *
     * @param string $table
     * @return ezcQueryUpdate
     */
    public function update( $table )
    {
        $table = $this->getIdentifier( $table );
        $this->table = $table;
        return $this;
    }

    /**
     * The update query will set the column $column to the value $expression.
     *
     * @param string $column
     * @param string $expression
     * @return ezcQueryUpdate
     */
    public function set( $column, $expression )
    {
        $column = $this->getIdentifier( $column );
        $expression = $this->getIdentifier( $expression );
        $this->values[$column] = $expression;
        return $this;
    }

    /**
     * Adds a where clause with logical expressions to the query.
     *
     * where() accepts an arbitrary number of parameters. Each parameter
     * must contain a logical expression or an array with logical expressions.
     * If you specify multiple logical expression they are connected using
     * a logical and.
     * where() could be invoked several times. All provided arguments 
     * added to the end of $whereString and form final WHERE clause of the query.
     * 
     *
     * Example:
     * <code>
     * $q->update( 'MyTable' )->where( $q->eq( 'id', 1 ) );
     * </code>
     *
     * @throws ezcQueryVariableParameterException if called with no parameters.
     * @param string|array(string) $... Either a string with a logical expression name
     * or an array with logical expressions.
     * @return ezcQueryUpdate
     */
    public function where()
    {
        if ( $this->whereString == null )
        {
            $this->whereString = 'WHERE ';
        }

        $args = func_get_args();
        $expressions = self::arrayFlatten( $args );
        if ( count( $expressions ) < 1 )
        {
            throw new ezcQueryVariableParameterException( 'where', count( $args ), 1 );
        }

        // glue string should be inserted each time but not before first entry
        if ( $this->whereString != 'WHERE ' ) 
        {
            $this->whereString .= ' AND ';
        }

        $this->whereString .= join( ' AND ', $expressions );
        return $this;
    }


    /**
     * Returns the query string for this query object.
     *
     * @todo wrong exception
     * @throws ezcQueryInvalidException if no table or no values have been set.
     * @return string
     */
    public function getQuery()
    {
        if ( $this->table == null || empty( $this->values ) )
        {
            $problem = $this->table == null ? 'table' : 'values';
            throw new ezcQueryInvalidException( "UPDATE", "No " . $problem . " set." );
        }
        $query = "UPDATE {$this->table} SET ";

        // build an append set part
        $setString = null;
        foreach ( $this->values as $key => $value )
        {
            if ( $setString === null )
            {
                $setString = "{$key} = {$value}";
            }
            else
            {
                $setString .= ", {$key} = {$value}";
            }
        }
        $query .= $setString;

        // append where part.
        if ( $this->whereString !== null )
        {
            $query .= " {$this->whereString}";
        }

        return $query;
    }
}
?>

<?php

class Kint_Parsers_objectIterateable extends kintParser {

	protected function _parse( & $variable )
	{
		if ( ! KINT_PHP53
			|| ! is_object( $variable )
			|| ! $variable instanceof Traversable
			|| stripos( get_class( $variable ), 'zend' ) !== FALSE // zf2 PDO wrapper does not play nice
		) { return FALSE;
        }


		$arrayCopy = iterator_to_array( $variable, TRUE );

		if ( $arrayCopy === FALSE ) { return FALSE;
        }

		$this->value = kintParser::factory( $arrayCopy )->extendedValue;
		$this->type  = 'Iterator contents';
		$this->size  = count( $arrayCopy );
	}
}

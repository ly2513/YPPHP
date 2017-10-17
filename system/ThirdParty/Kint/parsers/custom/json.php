<?php

class Kint_Parsers_Json extends kintParser {

	protected function _parse( & $variable )
	{
		if ( ! KINT_PHP53
			|| ! is_string( $variable )
			|| ! isset( $variable{0} ) || ( $variable{0} !== '{' && $variable{0} !== '[' )
			|| ( $json = json_decode( $variable, TRUE ) ) === NULL
		) { return FALSE;
        }

		$val = (array) $json;
		if ( empty( $val ) ) { return FALSE;
        }

		$this->value = kintParser::factory( $val )->extendedValue;
		$this->type  = 'JSON';
	}
}

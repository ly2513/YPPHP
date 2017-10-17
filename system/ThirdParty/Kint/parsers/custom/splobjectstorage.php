<?php

class Kint_Parsers_SplObjectStorage extends kintParser {

	protected function _parse( & $variable )
	{
		if ( ! is_object( $variable ) || ! $variable instanceof SplObjectStorage ) { return FALSE;
        }

		/**
 * @var $variable SplObjectStorage 
*/

		$count = $variable->count();
		if ( $count === 0 ) { return FALSE;
        }

		$variable->rewind();
		while ( $variable->valid() ) {
			$current       = $variable->current();
			$this->value[] = kintParser::factory( $current );
			$variable->next();
		}

		$this->type = 'Storage contents';
		$this->size = $count;
	}
}

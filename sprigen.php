<?php

class Sprigen {
	var $options = array();

	function __construct() {
		$this -> loadOptions();
		if( file_exists( $this -> options[ 'css' ][ 0 ] ) )
			unlink( $this -> options[ 'css' ][ 0 ] );

		foreach( $this -> options[ 'input' ] as $id => $file ) {
			$files = $this -> readFile( $file );
			$sprite = $this -> calculateSprite( $files );
			$this -> generateSprite( $sprite, $this -> options[ 'output' ][ $id ] );
			$this -> generateCSS( $sprite, $this -> options[ 'css' ][ 0 ], $this -> options[ 'css_out' ][ $id ] );
		}
	}
	
	function loadOptions() {
		global $argv;
		array_shift( $argv ); // remove file name

		foreach( $argv as $val ) {
			if( substr( $val, 0, 2 ) == '--' ) {
				$command = substr( $val, 2 );
				$this -> options[ $command ] = array();
			} else $this -> options[ $command ][] = $val;
		}
	}

	function readFile( $file ) {
		$f = fopen( $file, 'r' );
		$files = array();

		while( ( $line = fgets( $f ) ) !== false )
			$files[] = dirname( $file ) . '/' . substr( $line, 0, -1 );

		return $files;
	}
	
	function calculateSprite( $files ) {
		$sprite = array(); $y = 0; $x = 0;

		foreach( $files as $file ) {
			list( $width, $height ) = getimagesize( $file );
			$sprite[] = array( $file, $width, $height );
		}
		
		usort( $sprite, create_function( '$a, $b', 'return $a[1] < $b[1] ? -1 : 1;' ) );
		
		foreach( $sprite as $id => $s ) {
			$sprite[ $id ] = array( $s[ 0 ], 0, $y, $s[ 1 ], $s[ 2 ] );
			$y += $s[ 2 ];
			$x = max( $s[ 1 ], $x );
		}
		

		return array( $x, $y, $sprite );
	}

	function generateSprite( $sprite, $out ) {
		touch( $out );
		$im = new Imagick();
		$im -> newImage( $sprite[ 0 ], $sprite[ 1 ], "#000000", 'png' );
		$im -> setImageOpacity( 0.0 );
		
		foreach( $sprite[ 2 ] as $s ) {
			$from = new Imagick( $s[ 0 ] );
			$im -> compositeImage( $from, $from -> getImageCompose(), $s[ 1 ], $s[ 2 ] );
			$from -> destroy();
		}

		$im -> writeImage( $out );
		$im -> destroy();	
	}

	function generateCSS( $sprite, $css, $out ) {
		$f = fopen( $css, "a+" );
		$ret = '';

		$names = array();
		foreach( $sprite[ 2 ] as $id => $s ) {
			$names[] = '.s' . substr( basename( $s[ 0 ] ), 0, -4 );
			$ret .= "{$names[$id]}{background-position:{$s[1]}px -{$s[2]}px;width:{$s[3]}px;height:{$s[4]}px;}";
		}

		$ret = implode( ',', $names ) . "{background-image:url('$out');}" . $ret;

		fwrite( $f, $ret );
		fclose( $f );
	}
}

$sprigen = new Sprigen();

?>

<?php
/*
The MIT License (MIT)

Copyright (c) 2014 Zachary Seguin

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/
  
require(dirname(__FILE__) . '/../lib/lightncandy.php');

class TemplateRenderer
{
	static public $views_directory = 'views';
	static public $partials_directory = "partials";
	
	static function showTemplate($template, $page_title = "", $data = array())
	{
	    /* Load Template */
	    $template = file_get_contents(static::$views_directory . "/$template.tmpl");

	    $phpStr = LightnCandy::compile($template, Array(
	       'basedir' => static::$partials_directory,
	       'flags' => LightnCandy::FLAG_ERROR_EXCEPTION | LightnCandy::FLAG_PARENT
			   | LightnCandy::FLAG_ELSE | LightnCandy::FLAG_SPVARS,
          'blockhelpers' => Array(
             'TemplateRenderer::if_eq',
             'TemplateRenderer::if_contains_weekday'
          ),
          'helpers' => Array(
             'format_date'
          )
	    ));

	    $renderer = LightnCandy::prepare($phpStr);

	    /* Render with Data */
	    $data['page_title'] = $page_title;
	    echo $renderer($data);
	}// End of showTemplate function
   
   static function if_eq($ctx, $args, $named)
   {
      for ($x = 0; $x < count($args) - 1; ++$x)
      {
         if ($args[$x] !== $args[$x + 1])
         {
            return null;
         }// End of if
      }// End of for
   
      return $ctx;
   }// End of if_eq function (helper)

   static function if_contains_weekday($ctx, $args, $named)
   {
      return ($args[0][$args[1]] == 1) ? $args[0] : null;
   }// End of if_eq function (helper)
}// End of class

TemplateRenderer::$views_directory = dirname(__FILE__) . '/../' . TemplateRenderer::$views_directory;
TemplateRenderer::$partials_directory = dirname(__FILE__) . '/../' . TemplateRenderer::$partials_directory;

function format_date($args, $named)
{
   return date($args[1], strtotime($args[0]));
}// End of format_date

?>
<?php

namespace TomasKarlik\Responses;

use Exception;
use InvalidArgumentException;
use Nette\Application\IResponse as NetteAppIResponse;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Nette\Object;
use Traversable;


/**
 * This file is part of the CsvResponse
 *
 * For the full copyright and license information, please view
 * the file LICENSE that was distributed with this source code.
 */
class CsvResponse extends Object implements NetteAppIResponse
{

	const SEPARATOR_COMMA = ',';
	const SEPARATOR_SEMICOLON = ';';
	const SEPARATOR_TAB = '	';

	const DEFAULT_OUTPUT_CHARSET = 'utf-8';

	/**
	 * @var array
	 */
	private $data;

	/**
	 * @var string
	 */
	private $glue = self::SEPARATOR_COMMA;

	/**
	 * @var string
	 */
	private $outputCharset = self::DEFAULT_OUTPUT_CHARSET;

	/**
	 * @var string
	 */
	private $outputFilename;


	/**
	 * @param mixed $data
	 * @param string $filename
	 */
	public function __construct($data, $filename = 'output.csv')
	{
		$this->traversable($data);

		$this->data = &$data;
		$this->outputFilename = $filename;
	}


	/**
	 * @param string $glue
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function setGlue($glue)
	{
		if (empty($glue) || preg_match('/[\n\r"]/s', $glue)) {
			throw new InvalidArgumentException(sprintf('%s: glue cannot be an empty or reserved character!', __CLASS__));
		}

		$this->glue = $glue;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getGlue()
	{
		return $this->glue;
	}


	/**
	 * @param string $charset
	 * @return self
	 */
	public function setOutputCharset($charset)
	{
		$this->outputCharset = $charset;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getOutputCharset()
	{
		return $this->outputCharset;
	}


	/**
	 * @param string $filename
	 * @return self
	 */
	public function setOutputFilename($filename)
	{
		$this->outputFilename = $filename;
		return $this;
	}


	/**
	 * @return string
	 */
	public function getOutputFilename()
	{
		return $this->outputFilename;
	}


	/**
	 * @param IRequest $httpRequest
	 * @param IResponse $httpResponse
	 */
	public function send(IRequest $httpRequest, IResponse $httpResponse)
	{
		$data = $this->getCsv();

		$httpResponse->setContentType('text/csv', $this->outputCharset);
		$httpResponse->setHeader('Content-Disposition', 'attachment; filename="' . $this->outputFilename . '"');
		$httpResponse->setHeader('Content-Length', strlen($data));

		echo $data;
	}


	/**
	 * @param mixed $data
	 * @throws InvalidArgumentException
	 */
	private function traversable(&$data)
	{
		if ($data instanceof Traversable) {
			$data = iterator_to_array($data);

		} elseif ( ! is_array($data)) {
			throw new InvalidArgumentException(sprintf('%s: data must be array or instance of Traversable, %s given!', __CLASS__,  gettype($data)));
		}		
	}


	/**
	 * @return bool
	 */
	private function isNeedRecode()
	{
	    return strcmp($this->outputCharset, self::DEFAULT_OUTPUT_CHARSET);
	}


	/**
	 * @param string $row
	 */
	private function recode(array &$row)
	{
		foreach($row as &$column) {
			$column = iconv(self::DEFAULT_OUTPUT_CHARSET, sprintf('%s//TRANSLIT', $this->outputCharset), $column);
		}
	}


	/**
	 * @return string
	 * @throws Exception
	 */
	private function getCsv()
	{
		if (empty($this->data)) {
			return '';
		}

		$buffer = fopen("php://memory", 'w');
		if ($buffer === FALSE) {
			throw new Exception(sprintf('%s: error create buffer!', __CLASS__));
		}

		$recode = $this->isNeedRecode();
		foreach ($this->data as &$row) {
			$this->traversable($row);
			if ($recode) {
				$this->recode($row);
			}
			fputcsv($buffer, $row, $this->glue);
		}
		rewind($buffer);

		$output = stream_get_contents($buffer);		
		fclose($buffer);

		return $output;
	}

}

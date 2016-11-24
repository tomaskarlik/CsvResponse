# CsvResponse
Simple CSV response for [Nette Framework](https://github.com/nette/nette).

```php
	use TomasKarlik\Responses\CsvResponse;

	public function actionExport()
	{
		$subscribers = [
			['honza', '2016-01-01'],
			['pepa', '2016-01-02'],
			['david', '2016-01-03']
		];

		$response = new CsvResponse($subscribers, 'subscribers.csv');
		$this->sendResponse($response);
	}
```
<?

class TRedirectList extends TBaseList
{
	function __construct($table, $fields, $defaultSort)
	{
		$fields["Cnt"] = array(
			"Type" => "integer",
			"Sort" => "count(rh.RedirectHitID)",
			"Caption" => "Redirects",
            "FilterType" => "having",
            "FilterField" => "count(rh.RedirectHitID)",
		);
		parent::__construct($table, $fields, $defaultSort);
		$this->SQL = "select r.RedirectID, r.Name, r.URL, count(rh.RedirectHitID) as Cnt
		from Redirect r
		left outer join RedirectHit rh on r.RedirectID = rh.RedirectID
		where 1 = 1
		[Filters]
		group by r.RedirectID, r.Name, r.URL";
	}
}

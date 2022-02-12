<?php

/*
 * Simple query parser to search our Dr Who subtitles
 *
 * Matthew Somerville, https://dracos.co.uk/
 * Version 1.5
 */

include '/usr/share/php/xapian.php';
include './config.php';

function search($query, $num = 20) {
	$db = new XapianDatabase(XAPIAN_DIR);
	$enquire = new XapianEnquire($db);

	$stemmer = new XapianStem("english");
	$qp = new XapianQueryParser();
	$valuerange = new XapianNumberValueRangeProcessor(0);

	$qp->set_stemmer($stemmer);
	$qp->set_database($db);
	$qp->set_stemming_strategy(XapianQueryParser::STEM_SOME);
	$qp->set_default_op(Query_OP_AND);
	$qp->add_boolean_prefix('align', 'A');
	$qp->add_boolean_prefix('colour', 'C');
	$qp->add_boolean_prefix('ep', 'E');
	$qp->add_boolean_prefix('noise', 'N');
	$qp->add_boolean_prefix('series', 'S');
	$qp->add_valuerangeprocessor($valuerange);

	$query = $qp->parse_query($query, XapianQueryParser::FLAG_BOOLEAN | XapianQueryParser::FLAG_PHRASE |
        	XapianQueryParser::FLAG_LOVEHATE | XapianQueryParser::FLAG_WILDCARD |
	        XapianQueryParser::FLAG_SPELLING_CORRECTION);

	$enquire->set_query($query);
	$enquire->set_sort_by_value(1, true);
	$matches = $enquire->get_mset(0, $num);

	$desc = $query->get_description();
	$estimate = $matches->get_matches_estimated();

	$out = array();
	$iter = $matches->begin();
	while (!$iter->equals($matches->end())) {
		$doc = $iter->get_document();
		$data = array('text' => $doc->get_data());
		$rank = $iter->get_rank() + 1;
		$termiter = $doc->termlist_begin();
		$terms = array();
		while (!$termiter->equals($doc->termlist_end())) {
			$term = $termiter->get_term();
			$prefix = substr($term, 0, 1);
			if ($prefix == 'A') {
				$data['align'] = substr($term, 1);
			} elseif ($prefix == 'B') {
				$data['begin'] = substr($term, 1);
			} elseif ($prefix == 'C') {
				$data['colour'] = substr($term, 1);
			} elseif ($prefix == 'E') {
				$data['ep'] = substr($term, 1);
			} elseif ($prefix == 'N') {
				$data['noise'] = substr($term, 1);
			} elseif ($prefix == 'I') {
				$data['pos'] = $term;
			} elseif ($prefix == 'S') {
				$data['series'] = substr($term, 1);
			} else {
				$data['terms'][] = $term;
			}
			$termiter->next();
		}
		$out[] = $data;
		$iter->next();
	}

	$db = null;

	return array(
		'query' => $desc,
		'estimate' => $estimate,
		'data' => $out,
	);
}


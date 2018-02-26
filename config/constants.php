<?php

return [

    'pach_acm'          => 'https://dl.acm.org/',
    'pach_ieee'         => 'http://ieeexplore.ieee.org/',
    'api_rest_ieee'     => 'http://ieeexplore.ieee.org/rest/',
    'pach_doi'          => 'https://doi.org/',
    'url_acm_abstract'  => 'https://dl.acm.org/tab_abstract.cfm?id=',
    'url_acm_citation'  => 'https://dl.acm.org/citation.cfm?id=',
    'cookie_capes'                  => 'JSESSIONID=C02F6F4815D4F1737525A8F737929E87; sto-id-%3FSaaS-A_prod%3FPMTNA03.prod.primo.1701=HNHIBMAK; JSESSIONID=66C01F9AE252CF6CCAB5AE7814ABB62A; PRIMO_RT=s=1517971019710&r=http%3A%2F%2Frnp-primo.hosted.exlibrisgroup.com%2Fprimo_library%2Flibweb%2Faction%2Fbasket.do%3Ffn%3Ddisplay%26fromUserArea%3Dtrue%26vid%3DCAPES_V1%26fromPreferences%3Dfalse%26fromLink%3DgotoeShelfUI&p=QWERTY',
    'user_agent'                    => $_SERVER["HTTP_USER_AGENT"],
    'cookie_google'                 => 'GSP=A=YTLx9g:CPTS=1517668153:LM=1517668153:S=GpNlOJK1VaFBRA-t; NID=123=lipMm-n_k8E4D1WzQgeUe4puuSd17nR2dtXj9mnkaP5lQG-eO466aw1b3YgnR5Wf7BD7oKb3qrpHq3o81GZWcdQfgZsGydEwRjqz3p8kxeLWg7yysf0VCCq0If0zNtDN',
    'file'                          => strtolower('Internet_of_Medical_Things_OR_Internet_of_healthcare_things OR Internet_of_M-health_Things'),
    'query_string'                  => urlencode('"Internet of Medical Things" OR "Internet of healthcare things" OR "Internet of M-health Things"'),
    'xsrf_google'                   => 'AMstHGQAAAAAWnMRp0OEUK1-sGxeA7FK3mM_6CqbxAo8',
    'api_rest_plu_ms_elsevier'      => 'https://api.plu.mx/widget/elsevier/artifact?type=doi&id=',
    'user_agent'                    => (!empty(@$_SERVER["HTTP_USER_AGENT"])) ? @$_SERVER["HTTP_USER_AGENT"] : "Mozilla/5.0 (Macintosh; Intel Mac OS X 10.13; rv:58.0) Gecko/20100101 Firefox/58.0",
    'source_ieee'                   => 'ieee',
    'source_acm'                    => 'acm',
    'source_elsevier_sciencedirect' => 'elsevier_sciencedirect',
    'source_capes'                  => 'portal_capes',
    'source_springer'               => 'springer',
    
];

?>
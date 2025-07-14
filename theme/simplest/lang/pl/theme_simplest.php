<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme functions.
 *
 * @package   theme_simplest
 * @copyright 2024, LMSwithAI <contact@lmswithai.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$string['backgroundimage'] = 'Grafika tła';
$string['backgroundimage_desc'] = 'Obraz do wyświetlenia jako tło witryny. Przesłany tutaj obraz tła zastąpi obraz tła w plikach predefiniowanych motywów.';
$string['brandcolor'] = 'Kolor marki';
$string['brandcolor_desc'] = 'Kolor akcentu.';
$string['change_style'] = 'Zmień styl szablonu';
$string['choosereadme'] = 'Szablon Simplest jest motywem potomnym szablonu Classic.';
$string['configtitle'] = 'Simplest';
$string['disableinspirationalquotes'] = 'Wyłącz blok cytatów';
$string['disableinspirationalquotesdesc'] = 'Zaznaczenie tego pola wyłączy blok cytatów';
$string['enddate'] = 'Zakończenie daty';
$string['enddate_desc'] = 'Data kiedy styl będzie wyłączony. Po tym czasie powróci do standardowego.';
$string['errordate'] = 'Błędna data. Musisz wpisać date zgodnie z formatem YYYY-MM-DD np.2022-03-17.';
$string['errormonth'] = 'Błędna ilość dni. Ten miesiąc w roku {$a->year} ma tylko {$a->days} dni.';
$string['generalsettings'] = 'Ustawienia ogólne';
$string['inspirationalquotes'] = 'Inspirujące cytaty';
$string['inspirationalquotesdef'] = 'Komputer zasłuży na miano inteligentnego, jeżeli spowoduje u człowieka przekonanie, że jest człowiekiem.|pl|Alan Turing
Niechęć do przyznania, że ludzkość może mieć jakichś rywali pod względem potęgi intelektu, występuje mocniej wśród intelektualistów niż wśród pozostałych ludzi – intelektualiści mają więcej do stracenia.|pl|Alan Turing
Rozumowanie matematyczne może być traktowane schematycznie jako wykonywanie połączenia dwóch obiektów, które możemy nazwać intuicją i pomysłowością.|pl|Alan Turing
Młody człowieku, w matematyce nie ma nic do zrozumienia, trzeba się po prostu przyzwyczajać.|pl|John von neumann
Czy maszyny nie mogą dokonywać czegoś, co powinno być opisane jako myślenie, ale co jest bardzo różne od tego, co robi człowiek? |pl|Alan Turing
Boję się nie tyle sztucznej inteligencji, ile naturalnej głupoty.|pl|joel de rosnay
Gdy osiągnie IQ 100 czy 150, nie będzie problemu. Ale gdy tysiąc albo 10 tysięcy, to co wtedy? To może być największe osiągnięcie ludzkości. Ale zarazem jej koniec.|pl|Stephen Hawking
Jeszcze przed 2029 r. boty nas dogonią. Będą nie do odróżnienia od ludzkiej inteligencji. Co więcej, zdołają przyswoić czyjś styl myślenia, osobowość i słownictwo tak, by wyręczać go np. w prowadzeniu bloga.|pl|Raymond Kurzweil
Każdy, kto pracuje nad sztuczną inteligencją, wie, że komputery pokonają nas we wszystkim.|pl|Gary Marcus
Mamy sztuczną inteligencję we wszystkich produktach. Od telewizorów Bravia, przez bezlusterkowe aparaty fotograficzne, po Xperie i PlayStation.|pl|Daichi Yamafuji
Na naszych oczach startuje czwarta rewolucja przemysłowa, uczące się komputery drastycznie zmienią nasze życie. Bardziej niż koło, prąd czy internet.|pl|Jen Hsun Huang
Na Ziemi zawsze rządził najinteligentniejszy gatunek. Dlaczego teraz miałoby być inaczej?|pl|Oren Etzioni
Sztuczna inteligencja jest potencjalnie groźniejsza od bomb atomowych.|pl|Elon Musk
W przeciągu najbliższych stu lat, komputery wyprzedzą ludzi, jeśli chodzi o inteligencję.|pl|Stephen Hawking
Sztuczna inteligencja w obsłudze klientów będzie z pewnością stosowana coraz częściej, a same rozwiązania staną się coraz doskonalsze.|pl|
Sztuczna inteligencja jest lepsza od naturalnej głupoty.|pl|
Sukces w tworzeniu sztucznej inteligencji byłby największym wydarzeniem w historii ludzkości. Niestety, może to być także ostatni, chyba że nauczymy się unikać ryzyka. |pl|Stephen Hawking
AI to narzędzie. Wybór, w jaki sposób zostanie wdrożony, należy do nas. |pl|
Wyobrażam sobie świat, w którym sztuczna inteligencja sprawi, że będziemy pracować wydajniej, żyć dłużej i mieć czystszą energię..|pl|Fei - Fei Li';
$string['inspirationalquotesdesc'] = 'Każdy cytat musi być w nowej lini.<br>
Postaraj się, aby cytaty nie były za długi!
<br>Dodatkowo możesz dodać po znaku "<strong>|</strong>" tagi rozszerzające cytat o wyświetlenie na konkretny język i autora cytatu.
<br> Przykład:
<br><div style="border:solid 1px #DEDEDE;background:#f8f9fa;color:#222222;padding:4px;"><pre>
<br>First quote.<strong>|</strong>en<strong>|</strong>Joel de Rosnay
<br>First quote in Polish.<strong>|</strong>pl<strong>|</strong>Joel de Rosnay
<br>Second quote, no author.<strong>|</strong>en
<br>Quote for all languages.<strong>||</strong>Joel de Rosnay</pre></div>
<br> Twoja platforma wspiera języki: <strong> {$a} </strong>';
$string['inspirationalquotesduration'] = 'Czas cytatów';
$string['inspirationalquotesdurationdesc'] = 'Czas wyświetlenia cytatu.';
$string['inspirationalquotessettings'] = 'Ustawienia inspirujące cytaty';
$string['limitedbydate'] = 'Włącz ograniczenie daty dla stylu';
$string['limitedbydate_desc'] = 'Włącza dany styl w określonej dacie. Po tym czasie styl powróci do domyślnego.';
$string['madeby'] = 'Stworzone przez';
$string['navbardark'] = 'Użyj ciemnego stylu paska nawigacyjnego';
$string['navbardark_desc'] = 'Zamienia kolory tekstu i tła paska nawigacyjnego u góry strony między ciemnymi a jasnymi.';
$string['othersettings'] = 'Inne ustawienia';
$string['pluginname'] = 'Simplest';
$string['preset'] = 'Style szablonu';
$string['preset:default'] = 'Domyślny';
$string['preset:easter'] = 'Wielkanoc';
$string['preset:mourning'] = 'Żałoba';
$string['preset:spring'] = 'Wiosna';
$string['preset_desc'] = 'Wybierz predefiniowane ustawienie, aby zmienić wygląd motywu.';
$string['presettemporary'] = 'Styl tymaczasowy';
$string['presettemporary_desc'] = 'Wybierz styl jaki ma się ustawić w podanym okresie czasowym.';
$string['privacy:metadata'] = 'Temat Simplest nie przechowuje żadnych personalnych danych.';
$string['rawscss'] = 'Surowy SCSS';
$string['rawscss_desc'] = 'Użyj tego pola, aby podać kod SCSS lub CSS, który zostanie wstrzyknięty na końcu arkusza stylów.';
$string['rawscsspre'] = 'Surowy początkowy SCSS';
$string['rawscsspre_desc'] = 'W tym polu możesz podać inicjujący kod SCSS, zostanie on wstrzyknięty przed wszystkim innym. Przez większość czasu będziesz używać tego ustawienia do definiowania zmiennych.';
$string['redir'] = 'Przekieruj do kursu';
$string['redir_desc'] = 'Przekieruj od razu do kursu jeżeli użytkownik jest zapisany tylko jeden kurs.';
$string['region-side-post'] = 'Prawo';
$string['region-side-pre'] = 'Lewo';
$string['reportbug'] = 'Znalazłeś/aś błąd w szablonie? Zgłoś go!';
$string['settingsother'] = 'Inne ustawienia:';
$string['settingspresettemporary'] = 'Ustawienia stylu tymczasowego:';
$string['simpleusermenu'] = 'Uproszczenie menu użytkownika';
$string['simpleusermenu_desc'] = 'Ukrywa link do kokpitu i profilu. Dodaje link do zmiany hasła.';
$string['startdate'] = 'Start daty';
$string['startdate_desc'] = 'Data kiedy styl będzie włączony.';


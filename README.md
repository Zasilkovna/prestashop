# Modul pro PrestaShop

## Informace o modulu

**Podporované jazyky:**

* čeština
* slovenština
* angličtina.

**Podporované verze PrestaShopu:**

* 1.2.x, 1.3.x, 1.4.x, 1.5.x, 1.6.x (testováno 1.2.4.0 – 1.6.0.6)
* Při problému s použitím v jiné verzi nás kontaktujte na adrese technicka.podpora@zasilkovna.cz.

**Poskytované funkce:**

* Instalace typu dopravce Zásilkovna
  * možnost rozlišení ceny dle cílové země (pomocí vícenásobné instalace dopravce)
  * možnost instalace jedné země vícekrát (např. určujete-li dobírku již v přepravci)
  * volba typu zobrazení stejná jako v průvodci vložením poboček (JS API)
  * vybraná pobočka se zobrazuje v detailu objednávky v uživatelské (front-office) i administrátorské (back-office) sekci a dále v informačním e-mailu o objednávce
  * podpora doručení na adresu přes Zásilkovnu (zahraničí, večerní expresy apod.)

* Možnost exportu souboru s objednávkami
  * možnost označit objednávky, export CSV souboru pro hromadné podání zásilek
  * vyznačení již exportovaných objednávek
  * automatické a manuální označení dobírek
  * konverze měn pomocí interních kurzů PrestaShopu při zasílání za hranice (i pokud nepoužíváte dobírky do zahraničí, vypočte se z objednávky alespoň hodnota zásilky pro účely pojištění)

## Instalace
1. Stáhnout soubor modulu
2. Přihlašte se do administrace PrestaShopu, otevřete kartu Moduly (Modules) zde v horní části dejte přidat nový modul – tento stažený soubor:

  [![screen1](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/1-stazeni.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/1-stazeni.png)

3. Stažený modul nainstalujte:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/2-instalace.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/2-instalace.png)

4. Mělo se zobrazit hlášení o úspěšné instalaci a výstražná ikona o varováních, což je v pořádku, bude nutné ještě provést nastavení:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/3-nainstalovano-varovani.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/3-nainstalovano-varovani.png)

5. V nastavení je nutné zadat klíč API. Váš klíč API je cf001e6c16561393 a v případě potřeby jej najdete také ve své klientské sekci, pod Můj účet:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/4-klic-api.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/4-klic-api.png)

6. Posledním krokem je vytvoření způsobu dopravy. Toto provedete taktéž v nastavení modulu:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/5-zpusob-dopravy.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/5-zpusob-dopravy.png)

  Poté již můžete modul Zásilkovna plně využívat.

7. Dále až budete mít nějaké objednávky se způsobem dopravy Zásilkovna, můžete si je exportovat v CSV formátu pro hromadné podání zásilek:

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/6-export-objednavek.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/6-export-objednavek.png)

8. Pokud využíváte doručení na adresu přes Zásilkovnu (např. do zahraničí), můžete standardně vytvořit nový způsob dopravy v PrestaShopu (nového dopravce, zcela mimo modul Zásilkovny) a v nastavení modulu Zásilkovny určit pod jakým způsobem dopravy se tento má exportovat pro systém Zásilkovny – objednávky se pak zobrazí na stejném místě jako objednávky na výdejní místa (viz minulý bod):

  [![screen2](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/7-doruceni-na-adresu.png)](https://raw.githubusercontent.com/Zasilkovna/prestashop/master/doc/img/7-doruceni-na-adresu.png)
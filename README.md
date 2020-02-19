# Modul pro PrestaShop 1.7

### Stažení modulu
[Aktuální verze 2.1.3 (Stáhnout »)](https://github.com/Zasilkovna/prestashop/raw/master/releases/prestashop-1.7-packetery-2.1.3.zip)

### Instalace 
1. Přihlašte se do administrace PrestaShopu, klikněte na záložku "Moduly -> Module Manager".:

![screen1](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/1-moduly-75%25.png)

2. V pravém horním rohu obrazovky klikněte na "Nahrát modul":

![screen2](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/2-nahratmodul-75%25.png)

3. Klikněte na "vybrat soubor" a najděte cestu ke staženému souboru modulu:

![screen3](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/3-nahrat-75%25.png)

### Konfigurace

Po úspěšné instalaci klikněte v hlavním menu na záložku "Objednávky -> Zásilkovna Orders" a vyberte záložku "Nastavení":

![screen4](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/4-success-75%25.png)

#### Nastavení modulu

Veškerá nastavení se ukládají automaticky, vždy po opuštění editovaného údaje.
V případě, že nemáte uživatelský účet na stránkách Zásilkovny, je možné v rámci testování funkcionality modulu či podávání zásilek požádat o testovací účet na mailu Zákaznického servisu: <info@zasilkovna.cz>:

 - **Klíč API**  - váš klíč API naleznete v [klientské sekci Zásilkovny](https://client.packeta.com/cs/support/) v části **Klientská podpora**
 - **Označení odesílatele** - označení odesílatele které máte nastaveno v [klientské sekci](https://client.packeta.com/cs/senders/) v seznamu odesílatelů
 - **Vynutit zemi** - vyberte země, které se budou nabízet v košíku eshopu při výběru výdejního místa Zásilkovny. Výběr provedete tak, že stisknete 
 klávesu *Ctrl* + levým tlačítkem myši vyberete požadovanou zemi.  Můžete vybrat více zemí zároveň.  Stejným způsobem zemi odeberete. Jestliže 
 nevyberete žádnou zemi, budou se nabízet automaticky všechny podporované země.
 - **Vynutit jazyk** - Jazyk widgetu pro výběr výdejních míst se nastavuje podle aktuálně zvolené jazykové mutace eshopu.  Pokud nastavíte vynucený jazyk,  
 nastaví se tento jazyk ve widgetu vždy, bez ohledu na nastavenou jazykovou mutaci eshopu.

#### Způsoby dopravy

 - Vytvoření nového způsobu dopravy provedete kliknutím na symbol "+" v tabulce "Přidání způsobu dopravy".
 - Otevře se vyskakovací okno, vlastnosti způsobu dopravy nastavte dle Vašich požadavků. Vybrat více zemí je možné 
 přidržením tlačítka "Ctrl" a kliknutím na jednotlivé požadované země
 - Způsob dopravy uložte a zkontrolujte správně vytvořený způsob dopravy
 - pro smazání způsobu dopravy klikněte na tlačítko "odstranit" 
 - pokud se jedná o způsob dopravy na dobírku klikněte ve sloupci "Je na dobírku" na červený křížek, který se změní 
 na zelené zatržítko. 

#### Způsoby platby
 - U každého způsobu platby můžete nastavit zda se jedná o platbu.  
 - Pokud se jedná o platební metodu na dobírku ve sloupci "Je na dobírku" klikněte na červený křížek, 
 který se automaticky změní na zelené zatržítko.
  
#### Seznam dopravců doručení na adresu
Modul podporuje doručení na adresu přes Zásilkovnu prostřednictvím externích dopravců. Ke každému dopravci můžete 
přiřadit externího dopravce Zásilkovny a ve sloupci "Je na dobírku" zvolíte, zda se jedná o přepravu na dobírku.

### Výdejní místa
V záložce výdejní místa naleznete informace o poslední aktualizaci seznamu poboček:
 - celkový počet poboček
 - datum a čas poslední aktualizace

Pobočky se aktualizují automatický.  Pro ruční aktualizaci seznamu poboček klikněte na tlačítko "Aktualizace poboček".

### Objednávky
V záložce "objednávky" naleznete seznam všech objednávek u kterých byla vybrán způsob dopravy přes Zásilkovnu.
 - U každé zásilky můžete:
    - Ve sloupci "Je na dobírku" nastavit/zrušit odeslání na dobírku.
    - Kliknutím na cílové výdejní místo se otevře okno , kde můžete změnit výdejní míst, kam bude zásilka doručena.  
 - Označené objednávky můžete kliknutím na tlačítko "CSV export" uložit do csv souboru, který poté nahrajete 
 do klientské sekce » Import zásilek.
 - Modul podporuje také automatické odesílání dat k zásilkám.  Označené objednávky odešlete automaticky kliknutím 
 na tlačítko "Podat vybrané zásilky."  Po úspěšném odeslání se u zásilky doplní její trasovací číslo.  
 Kliknutím na trasovací číslo se otevře stránka sledování zásilky.
 - U zásilek které byly podány přes automatické odeslání a mají vyplněné trasovací číslo můžete 
 vytisknout štítky ve formátu pdf.  Objednávky označte a klikněte na tlačítko "Stažení štítků ve formátu .PDF".
 
  ## Informace o modulu
  
  **Podporované jazyky:**
  - čeština
  - angličtina
  
  #### Podporovaná verze
  - PrestaShop verze 1.7.x
  - Při problému s použitím modulu nás kontaktujte na adrese technicka.podpora@zasilkovna.cz
  
  #### Poskytované funkce
  - Integrace widgetu pro výběr výdejních míst v košíku eshopu
  - Doručení na adresu přes externí dopravce Zásilkovny
  - Automatický export zásilek do systému Zásilkovny
  - Export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/).
  
  ### Omezení
  - modul v současné době nepodporuje multistore
  - modul je určen pouze pro výchozí košíky PrestaShopu.  Pokud používáte nějaký one page checkout modul košíku třetí strany,  modul nemusí správně
  fungovat.

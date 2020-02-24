[Návod v češtině](#modul-pro-prestashop-17)

# Module for PrestaShop 1.7

### Download link
[Download version 2.1.3](https://github.com/Zasilkovna/prestashop/raw/master/releases/prestashop-1.7-packetery-2.1.3.zip)

### Installation
1. Log in to the PrestaShop administration, click on the "Modules -> Module Manager" tab:
![screen1](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/screenshot_1.png?raw=true)
2. In the upper right corner of the screen, click on "Load module":
![screen2](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/screenshot_2.png?raw=true)
3. Click on "select file" and find the path to the downloaded module file:
![screen3](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/screenshot_3.png?raw=true)

### Configuration
After successful installation, click on the **Orders** -> **Packeta Orders** tab in the main menu and select the **Settings** tab.

#### Module configuration
All settings are saved automatically whenever you leave the edited data. If you do not have a user account 
on the Packeta site, it is possible to request a test account at the Customer Service email [info@zasilkovna.cz](mailto:info@zasilkovna.cz):
- **API key** – you can find your key in the client section of Packeta in the *Client support* tab
- **Sender name** - the sender name you have set in the client section of the senders list
- **Force country** - select the countries that will be offered in the e-shop cart when selecting a Packeta pickup 
point, press Ctrl + left mouse button to select the desired country. You can select multiple countries at the same 
time. In the same way, you will remove the country. If no country is selected, all supported countries will 
be offered automatically.
- **Force language** - The language of the *pickup point selection* widget is set according to the currently 
selected eshop language version. If you set a forced language, this language is always set in the widget, regardless 
of the language version of the eshop.

#### Shipping methods
- To create a new shipping method, click the "+" symbol in the **Add shipping method** table.
- A pop-up window opens, set the shipping method according to your requirements. Multiple countries can be selected 
by holding down the "Ctrl" button and clicking on the desired countries.
- Save the shipping method and check if the shipping method is saved correctly.
- Click on the *delete* button to delete the shipping method.

#### Payment methods
- For each form of payment, you can set whether the payment is cash on delivery.	
- If the payment method is cash on delivery in the **Is cash on delivery** column, click on the red cross that will 
automatically change to a green check mark.
#### List of carriers delivery to address
The module supports delivery to an address by Packeta via external carriers. You can assign an external 
carrier of Packeta to each carrier and choose **Cash on Delivery** in the **Cash on Delivery** column.

### Pickup points
You can find information about the last update of the list of pickup points in the tab **pickup points**.

### Orders
In the **Orders** tab you will find a list of all orders for which the Packeta shipping method has been selected.
- For each packet you can:
    - In the "Cash on delivery" column, set / cancel cash on delivery.
    - Clicking on the destination pickup point opens a window where you can change the pickup points to which the 
    consignment will be delivered.
- Marked orders can be saved to csv file by clicking on the **CSV export** button which you will upload then to 
the client section in » Shipments import.
- The module also supports automatic sending of data to shipments. Send marked orders automatically by clicking on 
the button "Submit selected shipments." After successful shipment, the tracking number of the shipment will 
be automatically added. By clicking the tracking number you will open the tracking page.
- For shipments that have been submitted via automatic dispatch and have a tracking number filled in, you can 
print labels in pdf format. Mark orders and click on **Download labels in .PDF format**.

## Informations about the module
#### Supported languages:
- czech
- english
#### Supported versions
- PrestaShop version 1.7.x
- If you have problems using the module, please contact us at [support@packeta.com](mailto:suppor@packeta.com)

#### Supported functions 
- Integration of widget for pickup points selections in the eshop cart
- Address delivery via Packeta external carriers
- Automatical export of orders to the Packeta system
- Export orders to the CSV file, which can be imported in the [client section](https://client.packeta.com/).

### Limitations
- Module does not currently support multistore.
- The module is intended only for PrestaShop cart. If you use a one-page checkout module for a third-party cart, 
the module may not work properly.

# Modul pro PrestaShop 1.7

### Stažení modulu
[Aktuální verze 2.1.3 (Stáhnout »)](https://github.com/Zasilkovna/prestashop/raw/master/releases/prestashop-1.7-packetery-2.1.3.zip)

### Instalace 
1. Přihlašte se do administrace PrestaShopu, klikněte na záložku "Moduly -> Module Manager".:

![screen1](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/1-moduly-75%25.png?raw=true)

2. V pravém horním rohu obrazovky klikněte na "Nahrát modul":

![screen2](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/2-nahratmodul-75%25.png?raw=true)

3. Klikněte na "vybrat soubor" a najděte cestu ke staženému souboru modulu:

![screen3](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/3-nahrat-75%25.png?raw=true)

### Konfigurace

Po úspěšné instalaci klikněte v hlavním menu na záložku "Objednávky -> Zásilkovna Orders" a vyberte záložku "Nastavení":

![screen4](https://github.com/Zasilkovna/prestashop/blob/master/doc/img/4-success-75%25.png?raw=true)

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

 - Vytvoření nového způsobu dopravy provedete kliknutím na symbol "+" v tabulce **Přidání způsobu dopravy**.
 - Otevře se vyskakovací okno, vlastnosti způsobu dopravy nastavte dle Vašich požadavků. Vybrat více zemí je možné 
 přidržením tlačítka **Ctrl** a kliknutím na jednotlivé požadované země
 - Způsob dopravy uložte a zkontrolujte správně vytvořený způsob dopravy
 - pro smazání způsobu dopravy klikněte na tlačítko **odstranit** 
 - pokud se jedná o způsob dopravy na dobírku klikněte ve sloupci **Je na dobírku** na červený křížek, který se změní 
 na zelené zatržítko. 

#### Způsoby platby
 - U každého způsobu platby můžete nastavit zda se jedná o platbu.  
 - Pokud se jedná o platební metodu na dobírku ve sloupci **Je na dobírku** klikněte na červený křížek, 
 který se automaticky změní na zelené zatržítko.
  
#### Seznam dopravců doručení na adresu
Modul podporuje doručení na adresu přes Zásilkovnu prostřednictvím externích dopravců. Ke každému dopravci můžete 
přiřadit externího dopravce Zásilkovny a ve sloupci **Je na dobírku** zvolíte, zda se jedná o přepravu na dobírku.

### Výdejní místa
V záložce výdejní místa naleznete informace o poslední aktualizaci seznamu poboček:
 - celkový počet poboček
 - datum a čas poslední aktualizace
 
Pobočky se aktualizují automaticky. Pro ruční aktualizaci seznamu poboček klikněte na tlačítko **Aktualizace poboček**.

### Objednávky
V záložce **objednávky** naleznete seznam všech objednávek u kterých byla vybrán způsob dopravy přes Zásilkovnu.
 - U každé zásilky můžete:
    - Ve sloupci **Je na dobírku** nastavit/zrušit odeslání na dobírku.
    - Kliknutím na cílové výdejní místo se otevře okno , kde můžete změnit výdejní míst, kam bude zásilka doručena.  
 - Označené objednávky můžete kliknutím na tlačítko **CSV export** uložit do csv souboru, který poté nahrajete 
 do [klientské sekce](https://client.packeta.com/) » Import zásilek.
 - Modul podporuje také automatické odesílání dat k zásilkám.  Označené objednávky odešlete automaticky kliknutím 
 na tlačítko **Podat vybrané zásilky**.  Po úspěšném odeslání se u zásilky doplní její trasovací číslo.  
 Kliknutím na trasovací číslo se otevře stránka sledování zásilky.
 - U zásilek které byly podány přes automatické odeslání a mají vyplněné trasovací číslo můžete 
 vytisknout štítky ve formátu pdf.  Objednávky označte a klikněte na tlačítko **Stažení štítků ve formátu .PDF**.
 
  ## Informace o modulu
  
  #### Podporované jazyky
  - čeština
  - angličtina
  
  #### Podporovaná verze
  - PrestaShop verze 1.7.x
  - Při problému s použitím modulu nás kontaktujte na adrese [technicka.podpora@zasilkovna.cz](mailto:technicka.podpora@zasilkovna.cz)
  
  #### Poskytované funkce
  - Integrace widgetu pro výběr výdejních míst v košíku eshopu
  - Doručení na adresu přes externí dopravce Zásilkovny
  - Automatický export zásilek do systému Zásilkovny
  - Export zásilek do csv souboru, který lze importovat v [klientské sekci](https://client.packeta.com/).
  
  ### Omezení
  - modul v současné době nepodporuje multistore
  - modul je určen pouze pro výchozí košíky PrestaShopu.  Pokud používáte nějaký one page checkout modul košíku třetí 
  strany,  modul nemusí správně
  fungovat.

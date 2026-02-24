{**
 * @copyright 2015-2026 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

{if $disabledCarriers|@count > 0}
    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function () {
            var select = document.querySelector("select[name='id_branch']");
            if (!select) {
                return;
            }

            {foreach $disabledCarriers as $carrierId}
                var option = select.querySelector("option[value='{$carrierId|escape:'quotes':'UTF-8'}']");
                if (option) {
                    option.disabled = true;
                }
            {/foreach}

            // Reset if selected option is disabled
            var selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.disabled) {
                select.value = "";
                select.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    </script>
{/if}

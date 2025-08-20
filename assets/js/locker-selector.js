(function (wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginArea = wp.components.PluginArea;
    var createElement = wp.element.createElement;
    var useEffect = wp.element.useEffect;
    var useState = wp.element.useState;
    var $ = jQuery;

    function LockerSelector() {
        const [lockers, setLockers] = useState([]);

        useEffect(() => {
            const addLockerButton = () => {
                const shippingOptions = document.querySelectorAll('.wc-block-components-radio-control__option-layout');
                shippingOptions.forEach(option => {
                    if (option.textContent.toLowerCase().includes('locker') && !option.querySelector('.select-locker-btn')) {
                        const button = document.createElement('button');
                        button.textContent = 'Selectare locker';
                        button.className = 'button select-locker-btn';
                        button.style.marginTop = '10px';
                        button.style.backgroundColor = '#ebe9eb';
                        button.style.color = '#515151';
                        button.style.padding = '5px 10px';
                        button.style.border = '1px solid #d3ced2';
                        button.style.borderRadius = '3px';
                        button.style.cursor = 'pointer';
                        button.onclick = handleLockerSelection;
                        option.appendChild(button);
                    }
                });
            };

            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        addLockerButton();
                    }
                });
            });

            observer.observe(document.body, {childList: true, subtree: true});

            addLockerButton();

            return () => observer.disconnect();
        }, []);
        
        function handleLockerSelection() {
            $.ajax({
                url: EuroparcelLockerData.ajaxUrl,
                //timeout: 30000,
                type: 'POST',
                data: {
                    action: 'eawb_get_lockers',
                    security: EuroparcelLockerData.nonce,
                },
                dataType: 'json',
                success: function (response) {
                    console.log('AJAX success. Response:', response);
                    if (Array.isArray(response)) {
                        const lockerOptions = response.map(locker => `${locker.id}: ${locker.name}`).join('\n');
                        const selectedLockerId = prompt(`Selectați un locker:\n${lockerOptions}`);
                        if (selectedLockerId) {
                            var hiddenInput = document.getElementById('selected_locker');
                            if (!hiddenInput) {
                                hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.id = 'selected_locker';
                                hiddenInput.name = 'selected_locker';
                                document.querySelector('form[name="checkout"]').appendChild(hiddenInput);
                            }
                            hiddenInput.value = selectedLockerId;
                            alert("Locker selectat: " + selectedLockerId);
                        }
                    } else {
                        console.error('Răspunsul nu este un array valid:', response);
                    }
                },
                error: function (xhr) {
                    console.error('Error:', xhr.responseText);
                    alert('Eroare la încărcarea lockerelor!');
                }
            });
        }

        return null; // Nu renderizăm nimic direct, totul se face prin manipularea DOM-ului
    }

    registerPlugin('europarcel-plugin', {
        render: LockerSelector,
        scope: 'woocommerce-checkout',
    });
})(window.wp);
<div class="row payment-plugin" id="paystackPayment" style="display: none;">
    <div class="col-md-10 col-sm-12 box-center center mt-4 mb-0">
        <div class="row">
            
            <div class="col-xl-12 text-center">
                <img class="img-fluid" src="{{ url('images/paystack/payment.png') }}" title="{{ trans('paystack::messages.Payment with Paystack') }}">
            </div>
            
            <!-- ... -->
        
        </div>
    </div>
</div>

@section('after_scripts')
    @parent
    <script>
        $(document).ready(function ()
        {
            var selectedPackage = $('input[name=package_id]:checked').val();
            var packagePrice = getPackagePrice(selectedPackage);
            var paymentMethod = $('#paymentMethodId').find('option:selected').data('name');
    
            /* Check Payment Method */
            checkPaymentMethodForPaystack(paymentMethod, packagePrice);
            
            $('#paymentMethodId').on('change', function () {
                paymentMethod = $(this).find('option:selected').data('name');
                checkPaymentMethodForPaystack(paymentMethod, packagePrice);
            });
            $('.package-selection').on('click', function () {
                selectedPackage = $(this).val();
                packagePrice = getPackagePrice(selectedPackage);
                paymentMethod = $('#paymentMethodId').find('option:selected').data('name');
                checkPaymentMethodForPaystack(paymentMethod, packagePrice);
            });
    
            /* Send Payment Request */
            $('#submitPostForm').on('click', function (e)
            {
                e.preventDefault();
        
                paymentMethod = $('#paymentMethodId').find('option:selected').data('name');
                
                if (paymentMethod != 'paystack' || packagePrice <= 0) {
                    return false;
                }
    
                $('#postForm').submit();
        
                /* Prevent form from submitting */
                return false;
            });
        });

        function checkPaymentMethodForPaystack(paymentMethod, packagePrice)
        {
            if (paymentMethod == 'paystack' && packagePrice > 0) {
                $('#paystackPayment').show();
            } else {
                $('#paystackPayment').hide();
            }
        }
    </script>
@endsection

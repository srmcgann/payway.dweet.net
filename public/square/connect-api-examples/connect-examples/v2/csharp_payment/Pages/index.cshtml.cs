﻿using System;
using System.Threading.Tasks;

using Square;
using Microsoft.AspNetCore.Mvc.RazorPages;
using Microsoft.Extensions.Configuration;

namespace sqRazorSample.Pages
{
    public class IndexModel : PageModel
    {
        public string WebPaymentsSdkUrl { get; set; }
        public string ApplicationId { get; set; }
        public string LocationId { get; set; }
        public string Currency { get; set; }
        public string Country { get; set; }

        private SquareClient client;

        public IndexModel(Microsoft.Extensions.Configuration.IConfiguration configuration)
        {
            var environment = configuration["AppSettings:Environment"] == "sandbox" ?
                Square.Environment.Sandbox : Square.Environment.Production;

            ApplicationId = configuration["AppSettings:ApplicationId"];
            LocationId = configuration["AppSettings:LocationId"];

            WebPaymentsSdkUrl = environment == Square.Environment.Sandbox ?
                "https://sandbox.web.squarecdn.com/v1/square.js" : "https://web.squarecdn.com/v1/square.js" ;


            client = new SquareClient.Builder()
                .Environment(environment)
                .AccessToken(configuration["AppSettings:AccessToken"])
                .Build();
        }

        public async Task OnGetAsync() {
            var result = await client.LocationsApi.RetrieveLocationAsync(locationId: this.LocationId);
            this.Country = result.Location.Country;
            this.Currency = result.Location.Currency;
        }
    }
}

using System.ClientModel.Primitives;
using System.Net;
using OpenAI;
using SpotifyAPI.Web;
using SpotifyAPI.Web.Http;

namespace Moodler.Helpers;

public class ProxyHelper (IConfiguration configuration)
{
    private readonly string _proxyAddress = configuration.GetSection("Proxy").Value ?? ""; // Your proxy address
    
    public HttpClient ConfigureProxy()
    {
        // Set up the proxy
        var proxy = new WebProxy(_proxyAddress, false)
        {
            UseDefaultCredentials = true // If the proxy requires authentication with the current user's credentials
        };
        
        // Set up the HttpClientHandler with the proxy
        var httpClientHandler = new HttpClientHandler
        {
            Proxy = proxy,
            UseProxy = true
        };
        
        var httpClient = new HttpClient(httpClientHandler);

        return httpClient;
    }

    public SpotifyClientConfig ConfigureSpotifyProxy(string token)
    {
        var httpClient = new NetHttpClient(new ProxyConfig("http://winproxy.server.lan", 3128)
        {
            SkipSSLCheck = true,
            BypassProxyOnLocal = true
        });
        
        return SpotifyClientConfig
            .CreateDefault()
            .WithToken(token)
            .WithHTTPClient(httpClient);
    }
}
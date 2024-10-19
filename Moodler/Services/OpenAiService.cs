using System.ClientModel;
using System.ClientModel.Primitives;
using System.Net;
using Moodler.Helpers;
using OpenAI;
using OpenAI.Chat;

namespace Moodler.Services;

public class OpenAiService(EncryptHelper encryptHelper, IWebHostEnvironment env, IConfiguration configuration) : IOpenAiService
{
    private readonly string _chatGptApiKey =
        env.IsDevelopment()
            ? encryptHelper.Decrypt(configuration.GetSection("Api:OpenAiKeyLocal").Value ?? "")
            : encryptHelper.Decrypt(configuration.GetSection("Api:OpenAiKey").Value ?? "");

    private readonly string _proxyAddress = configuration.GetSection("Proxy").Value ?? ""; // Your proxy address
    
    public ChatClient GetChatClient()
    {
        var apiKeyCredential = new ApiKeyCredential(_chatGptApiKey);

        return env.IsDevelopment()
            ? new ChatClient(model: "gpt-4o-mini", apiKeyCredential)
            : new ChatClient(model: "gpt-4o-mini", apiKeyCredential, SetProxyOptions());
        
    }

    private OpenAIClientOptions SetProxyOptions()
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

        OpenAIClientOptions options = new()
        {
            Transport = new HttpClientPipelineTransport(httpClient),
        };

        return options;
    }
}
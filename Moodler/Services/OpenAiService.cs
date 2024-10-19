using System.ClientModel;
using System.ClientModel.Primitives;
using System.Net;
using Moodler.Helpers;
using OpenAI;
using OpenAI.Chat;

namespace Moodler.Services;

public class OpenAiService(EncryptHelper encryptHelper, IWebHostEnvironment env, IConfiguration configuration, ProxyHelper proxyHelper) : IOpenAiService
{
    private readonly string _chatGptApiKey =
        env.IsDevelopment()
            ? encryptHelper.Decrypt(configuration.GetSection("Api:OpenAiKeyLocal").Value ?? "")
            : encryptHelper.Decrypt(configuration.GetSection("Api:OpenAiKey").Value ?? "");

   
    
    public ChatClient GetChatClient()
    {
        var apiKeyCredential = new ApiKeyCredential(_chatGptApiKey);

        return new ChatClient(model: "gpt-4o-mini", apiKeyCredential);
        /*return env.IsDevelopment()
            ? new ChatClient(model: "gpt-4o-mini", apiKeyCredential)
            : new ChatClient(model: "gpt-4o-mini", apiKeyCredential, SetProxyOptions());*/

    }

    private OpenAIClientOptions SetProxyOptions()
    {
        var httpClient = proxyHelper.ConfigureProxy();

        OpenAIClientOptions options = new()
        {
            Transport = new HttpClientPipelineTransport(httpClient),
        };

        return options;
    }
}
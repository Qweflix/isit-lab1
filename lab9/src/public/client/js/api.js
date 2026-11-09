import {API_BASE} from './config.js';
export class ApiError extends Error {
  constructor(message,status,errors={}) {super(message);this.status=status;this.errors=errors;}
}
export function clearSession(){localStorage.removeItem('authToken');}
export async function request(path,{method='GET',body,signal}={}){
  const token=localStorage.getItem('authToken'),headers={Accept:'application/json'};
  if(token) headers.Authorization=`Bearer ${token}`;
  if(body!==undefined) headers['Content-Type']='application/json';
  let response;
  try{response=await fetch(API_BASE+path,{method,headers,body:body===undefined?undefined:JSON.stringify(body),signal});}
  catch(e){if(e.name==='AbortError')throw e;throw new ApiError('Нет связи с сервером. Проверьте подключение и повторите.',0);}
  if(response.status===204)return null;
  let data;try{data=await response.json();}catch{throw new ApiError('Сервер вернул некорректный ответ',response.status);}
  if(!response.ok){
    if(response.status===401&&path!=='/auth/login'){clearSession();window.dispatchEvent(new Event('session-expired'));}
    throw new ApiError(data.error||'Ошибка сервера',response.status,data.errors);
  }return data;
}

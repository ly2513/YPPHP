namespace php  Services.Shared

struct SharedStruct {
  1: i32 key
  2: string value
}

service Shared {
  SharedStruct getStruct(1: i32 key)
}